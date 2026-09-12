# Mini Farm Manager — Design Document

This document records the domain model, business rules, application structure and testing strategy for the Mini Farm Manager exercise. It was written _before_ implementation so that decisions are made once, deliberately, and can be referenced from the README.

Requirements: see [`requirements.md`](requirements.md).

---

## 1. Overview and stack

| Layer        | Choice                                                            | Why                                                                                                             |
| ------------ | ----------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Backend      | Laravel 13 / PHP 8.4                                              | Required by the brief                                                                                           |
| Frontend     | Inertia 3 + Vue 3.5 + TypeScript                                  | Server-driven pages without a separate JSON API: less glue code, validation errors flow straight into `useForm` |
| UI           | Tailwind 4 + shadcn-vue components (`resources/js/components/ui`) | Already shipped with the starter kit; consistent look with minimal effort                                       |
| Routing glue | Laravel Wayfinder                                                 | Typed route/action helpers in TypeScript, no hard-coded URLs                                                    |
| Database     | SQLite                                                            | Zero setup for reviewers; schema is plain enough to run on MySQL/Postgres unchanged                             |
| Tests        | PHPUnit 12 (feature tests)                                        | Starter kit default                                                                                             |

**Authentication**: the brief says authentication is not required. The starter kit ships with Fortify (login, registration, 2FA, passkeys). We keep it installed — removing it costs time and buys nothing — but all farm routes are registered **outside** the `auth` middleware group so a reviewer can use the app without logging in.

---

## 2. Domain model

```
paddocks 1 ──< animals            (animals.current_paddock_id, nullable)
animals  1 ──< animal_movements   (from_paddock_id?, to_paddock_id?)
animals  1 ──< health_records
```

### `animals`

| Column               | Type                         | Constraints / notes                                           |
| -------------------- | ---------------------------- | ------------------------------------------------------------- |
| `id`                 | bigint PK                    |                                                               |
| `tag_number`         | string                       | required, **unique**                                          |
| `name`               | string                       | nullable ("if applicable")                                    |
| `species`            | string (enum `Species`)      | `cattle`, `sheep`, `goat`, `pig`, `horse`, `chicken`, `other` |
| `sex`                | string (enum `Sex`)          | `male`, `female`                                              |
| `date_of_birth`      | date                         | required, must not be in the future                           |
| `breed`              | string                       | nullable                                                      |
| `status`             | string (enum `AnimalStatus`) | `active` (default), `sold`, `deceased`                        |
| `notes`              | text                         | nullable                                                      |
| `current_paddock_id` | FK → `paddocks`              | nullable, `nullOnDelete`                                      |
| timestamps           |                              |                                                               |

### `paddocks`

| Column     | Type         | Constraints / notes  |
| ---------- | ------------ | -------------------- |
| `id`       | bigint PK    |                      |
| `name`     | string       | required, **unique** |
| `capacity` | unsigned int | required, `>= 1`     |
| `notes`    | text         | nullable             |
| timestamps |              |                      |

### `animal_movements`

| Column            | Type            | Constraints / notes      |
| ----------------- | --------------- | ------------------------ |
| `id`              | bigint PK       |                          |
| `animal_id`       | FK → `animals`  | `cascadeOnDelete`        |
| `from_paddock_id` | FK → `paddocks` | nullable, `nullOnDelete` |
| `to_paddock_id`   | FK → `paddocks` | nullable, `nullOnDelete` |
| `moved_at`        | datetime        | required                 |
| `notes`           | text            | nullable                 |
| timestamps        |                 |                          |

Index on `(animal_id, moved_at)`.

Semantics of the nullable ends:

- `from = null, to = X` — initial placement (animal enters the farm / first assignment).
- `from = X, to = null` — animal leaves its paddock (status changed to `sold` or `deceased`).
- `from = X, to = Y` — ordinary move.

### `health_records`

| Column        | Type                             | Constraints / notes                                      |
| ------------- | -------------------------------- | -------------------------------------------------------- |
| `id`          | bigint PK                        |                                                          |
| `animal_id`   | FK → `animals`                   | `cascadeOnDelete`                                        |
| `recorded_on` | date                             | required, not in the future                              |
| `type`        | string (enum `HealthRecordType`) | `vaccination`, `treatment`, `injury`, `checkup`, `other` |
| `description` | string                           | required, short summary                                  |
| `notes`       | text                             | nullable                                                 |
| timestamps    |                                  |                                                          |

Index on `(animal_id, recorded_on)`.

### Enums

PHP backed enums in `app/Enums/` (`Species`, `Sex`, `AnimalStatus`, `HealthRecordType`), each with a `label()` method. Controllers pass them to Vue as `{ value, label }[]` option lists so the frontend never hard-codes the values.

### Design decision: store `current_paddock_id` or derive it?

**Stored.** Every read path — animal list, paddock page, dashboard, and the capacity check itself — needs "where is this animal now". Deriving it from the latest movement means a correlated subquery on every one of those queries.

The trade-off is a denormalised column that can drift from the movement history. Mitigations:

- `current_paddock_id` is **only ever written inside the same database transaction** that inserts the corresponding `animal_movements` row (see `MoveAnimal` below). No other code path touches it.
- A feature test asserts that after a sequence of moves, `current_paddock_id` equals the `to_paddock_id` of the animal's latest movement.

The movement table remains the source of truth for _history_; the column is a cache of its most recent row.

---

## 3. Business rules

1. **Occupancy.** `occupancy(paddock) = count(animals where current_paddock_id = paddock.id AND status = active)`. Non-active animals never occupy a paddock (rule 3 guarantees they have no `current_paddock_id` anyway).

2. **Moving an animal** into paddock _T_ is allowed only when:
    - the animal's status is `active`;
    - _T_ differs from the animal's current paddock;
    - `occupancy(T) < T.capacity`.

    The move runs inside `DB::transaction()` and takes `lockForUpdate()` on the target paddock row before counting, so two concurrent moves cannot both pass the check and overfill the paddock.

3. **Status change away from `active`** (`sold` or `deceased`) removes the animal from its paddock: a movement `(from = current, to = null)` is recorded and `current_paddock_id` is set to null, in one transaction. Changing status back to `active` leaves the animal unplaced; the user assigns a paddock through the normal move action (the edit form shows a hint explaining this).

4. **Initial placement.** The "add animal" form has an optional paddock select. If provided, the animal is created and immediately placed via the same move logic, so the capacity rule applies from the very first record.

5. **Editing a paddock's capacity** below its current occupancy is rejected with a validation error. This keeps "a paddock is never over capacity" as a hard invariant rather than a soft warning.

6. **Capacity warning** _(the additional feature)_. Each paddock has a derived `occupancy_state`:

    | Condition                          | State     | UI                                  |
    | ---------------------------------- | --------- | ----------------------------------- |
    | `occupancy / capacity < threshold` | `ok`      | neutral                             |
    | `threshold <= ratio < 1`           | `warning` | amber highlight                     |
    | `ratio >= 1`                       | `full`    | red highlight, move target disabled |

    `threshold` lives in `config/farm.php` (`capacity_warning_threshold`, default `0.8`). The state is computed server-side in one place (`Paddock::occupancyState()`, using `withCount` for lists) so every page agrees.

7. **Deletion** is out of scope: the brief asks only for add / edit / view. No destroy routes are exposed. This also sidesteps "what happens to history when a paddock is deleted" — the `nullOnDelete` FKs are there only as a safety net.

---

## 4. Application layer

### Actions (single-purpose classes in `app/Actions/`)

- **`MoveAnimal`** — the one non-trivial write in the system.
  `handle(Animal $animal, ?Paddock $to, CarbonInterface $movedAt, ?string $notes = null): AnimalMovement`
  Opens a transaction, locks the target paddock, checks rules 2/3, inserts the movement, updates `current_paddock_id`. Violations throw `ValidationException` keyed to the form field (`to_paddock_id`) so Inertia renders them inline.

- **`ChangeAnimalStatus`** — applies a status change; when leaving `active` it delegates to `MoveAnimal` with `to = null`.

### Form Requests (`app/Http/Requests/`)

`StoreAnimalRequest`, `UpdateAnimalRequest`, `StorePaddockRequest`, `UpdatePaddockRequest` (includes the "capacity >= occupancy" rule), `StoreAnimalMovementRequest`, `StoreHealthRecordRequest`.

Validation lives here; the Actions enforce the invariants that depend on database state.

### Controllers (`app/Http/Controllers/`)

| Controller                 | Methods                                              |
| -------------------------- | ---------------------------------------------------- |
| `DashboardController`      | `__invoke`                                           |
| `AnimalController`         | `index`, `create`, `store`, `show`, `edit`, `update` |
| `PaddockController`        | `index`, `create`, `store`, `show`, `edit`, `update` |
| `AnimalMovementController` | `store`                                              |
| `HealthRecordController`   | `store`                                              |

Controllers stay thin: validate via Form Request, call an Action or a simple Eloquent write, redirect with a flash message.

### Models, factories, seeder

- Models: `Animal`, `Paddock`, `AnimalMovement`, `HealthRecord` with enum casts and relationships.
- Factories with states: `AnimalFactory::active() / sold() / deceased() / inPaddock(Paddock)`, `PaddockFactory::withCapacity(int)`.
- `FarmSeeder` builds a realistic demo farm: a handful of paddocks (including one at ~85 % and one full), ~20 animals across species, a movement history and a few health records — so the dashboard and warnings are visible immediately after `php artisan migrate --seed`.

---

## 5. Routes (`routes/web.php`, all public)

```
GET  /                                 → redirect to dashboard
GET  /dashboard                        dashboard

GET  /animals                          animals.index      filters: species, status, paddock, search (tag/name)
GET  /animals/create                   animals.create
POST /animals                          animals.store
GET  /animals/{animal}                 animals.show       movement history, health history, move dialog, add health record
GET  /animals/{animal}/edit            animals.edit
PUT  /animals/{animal}                 animals.update
POST /animals/{animal}/movements       animals.movements.store
POST /animals/{animal}/health-records  animals.health-records.store

GET  /paddocks                         paddocks.index     occupancy + warning badge per paddock
GET  /paddocks/create                  paddocks.create
POST /paddocks                         paddocks.store
GET  /paddocks/{paddock}               paddocks.show      animals currently inside
GET  /paddocks/{paddock}/edit          paddocks.edit
PUT  /paddocks/{paddock}               paddocks.update
```

Fortify and settings routes remain as generated by the starter kit. The sidebar's `NavUser` component must tolerate a guest (`auth.user === null`) and show a "Log in" link instead of the user menu.

---

## 6. Frontend

Pages under `resources/js/pages/`:

| Page                                       | Content                                                                                                                                                                                                  |
| ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Dashboard.vue`                            | Stat cards (active animals, paddocks, paddocks needing attention); animals by species; paddock occupancy list with progress bars (amber ≥ 80 %, red when full); recent movements; capacity-warning panel |
| `animals/Index.vue`                        | Filterable table: tag, name, species, sex, age, status badge, current paddock                                                                                                                            |
| `animals/Create.vue`, `animals/Edit.vue`   | Shared `AnimalForm`; create offers optional initial paddock                                                                                                                                              |
| `animals/Show.vue`                         | Details, current paddock, "Move" dialog, movement history, health history + "Add record" form                                                                                                            |
| `paddocks/Index.vue`                       | Cards/table with occupancy bar and state badge                                                                                                                                                           |
| `paddocks/Create.vue`, `paddocks/Edit.vue` | Shared `PaddockForm`                                                                                                                                                                                     |
| `paddocks/Show.vue`                        | Capacity summary and the list of animals currently inside                                                                                                                                                |

Shared components in `resources/js/components/farm/`: `AnimalForm`, `PaddockForm`, `MoveAnimalDialog`, `HealthRecordForm`, `OccupancyBar`, `AnimalStatusBadge`, `EmptyState`.

Conventions:

- Forms use Inertia `useForm` and Wayfinder action helpers (`@/actions/App/Http/Controllers/...`).
- Server flashes `success` through `HandleInertiaRequests`; the layout shows it with the existing `sonner` toaster.
- Sidebar navigation: Dashboard, Animals, Paddocks.
- Shared TypeScript types in `resources/js/types/farm.d.ts`.
- Empty states everywhere a list can be empty (new install without seeding).

---

## 7. Testing strategy

Feature tests (PHPUnit, `RefreshDatabase`, factories), covering behaviour the farmer would notice if it broke:

| Test class             | Cases                                                                                                                                                                                                                                         |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `AnimalTest`           | create with valid data; duplicate tag rejected; DOB in the future rejected; create with initial paddock records a `null → paddock` movement; update basic fields; status → `sold` removes from paddock and writes a `paddock → null` movement |
| `PaddockTest`          | create / update validation; capacity below current occupancy rejected; show lists only active animals currently inside                                                                                                                        |
| `AnimalMovementTest`   | successful move updates `current_paddock_id` and appends history; rejected when target is full; rejected when target equals current; rejected when animal is not active; `current_paddock_id` matches latest movement after several moves     |
| `HealthRecordTest`     | store with valid data; validation failures; animal page lists records newest first                                                                                                                                                            |
| `DashboardTest`        | animal counts and species breakdown; warnings panel includes a paddock at ≥ 80 % and excludes one below                                                                                                                                       |
| `PaddockOccupancyTest` | `occupancyState()` boundaries: 79 % → ok, 80 % → warning, 100 % → full; sold animals do not count                                                                                                                                             |

Not tested: Fortify auth (already covered by starter kit tests), pure layout.

---

## 8. Deliberately left out / production improvements

Left out to respect the 3–4 hour budget:

- Deleting animals, paddocks or records
- Recording _who_ performed a movement (no auth in scope)
- Weight / medication history, photos, documents
- Bulk moves (move a whole mob between paddocks)
- Pagination beyond a simple paginator on the animals list

Before production:

- Real concurrency test of the capacity lock against MySQL/Postgres
- Authorisation (multiple farms / users)
- Soft deletes and an audit log
- Reporting exports (CSV) and date-range filters on history
- Accessibility and mobile review of the forms

---

## 9. AI-assisted development

Claude Code (Anthropic) was used to scaffold the project via `laravel new … --boost`, to draft this document from a guided discussion, and to generate boilerplate, pages and tests. Every business rule in §3 was decided in conversation, and all generated code is reviewed and verified by running the test suite and the app locally. Details go in the README's "How AI tools were used" section.
