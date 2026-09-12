# Mini Farm Manager

A small farm-management application built for the Laravel + Vue developer exercise. A farmer can register animals, keep them in paddocks, move them around without ever overfilling a paddock, and record each animal's health history. A dashboard shows the state of the farm at a glance, and paddocks that are nearly full are flagged before they become a problem.

- **Live demo:** https://minifarm.wangxiaoxiang.com/ — no login needed; the demo farm is seeded
- **Stack:** Laravel 13 · PHP 8.4 · Inertia 3 · Vue 3.5 · TypeScript · Tailwind 4 · SQLite · PHPUnit 12
- **Design document:** [`docs/design.md`](docs/design.md) — written before implementation; the domain model, business rules and testing strategy live there
- **Exercise brief:** [`docs/requirements.md`](docs/requirements.md)

---

## How to run

Requirements: PHP 8.4+, Composer, Node 20+ / npm.

```bash
git clone https://github.com/wangxiaoxiang5599/MiniFarmManager.git
cd MiniFarmManager

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite        # on Windows: type nul > database\database.sqlite
php artisan migrate --seed            # seeds a demo farm (see below)

composer run dev                      # serves at http://localhost:8000 (app + Vite + queue)
```

No login is needed — open http://localhost:8000 and you land on the dashboard.

Run the test suite with:

```bash
php artisan test
```

### Demo data

`php artisan migrate --seed` (or `php artisan db:seed`) builds a small farm through the same code paths the UI uses, so movement history and paddock occupancy are internally consistent:

- 5 paddocks — one **full** (Home Paddock), two **nearly full** (North Paddock, Orchard Paddock), two with room
- 36 animals across cattle, sheep, goats and chickens, including one unplaced animal, two sold and one deceased
- a few weeks of movements and ~20 health records

Run `php artisan migrate:fresh --seed` to reset it.

---

## What's included

| Area                                                                         | Where                                                                      |
| ---------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| Animals — list (search + filters), add, edit, detail                         | `AnimalController`, `resources/js/pages/animals/`                          |
| Paddocks — list, add, edit, detail with current animals                      | `PaddockController`, `resources/js/pages/paddocks/`                        |
| Movements — move dialog, full history, current paddock, capacity enforcement | `App\Actions\MoveAnimal`, `AnimalMovementController`                       |
| Health records — add from the animal page, history newest-first              | `HealthRecordController`                                                   |
| Dashboard — counts, species breakdown, occupancy, warnings, recent moves     | `DashboardController`, `resources/js/pages/Dashboard.vue`                  |
| **Additional feature: capacity warnings**                                    | `App\Enums\OccupancyState`, `Paddock::occupancyState()`, `config/farm.php` |
| Tests — 82 feature tests                                                     | `tests/Feature/`                                                           |

---

## Additional feature: paddock capacity warnings

**What it does.** Every paddock has a derived state — `ok`, `warning` (≥ 80 % occupied) or `full`. The state drives an amber/red highlight on paddock cards, the progress bars, the animal-move dialog (full paddocks are shown but disabled), and a "Capacity warnings" panel on the dashboard that lists the paddocks needing attention, most-full first.

**Why this one.** The brief already requires a hard stop at capacity. In practice a farmer wants to know _before_ that point — when they go to move a mob and find the destination has one place left, it's already too late to plan. A threshold warning is the smallest feature that turns the capacity rule from a blocker into a planning aid, and it reuses the occupancy calculation the capacity check already needs.

**Assumptions.** 80 % is a sensible default for "start thinking about it"; it lives in `config/farm.php` (`FARM_CAPACITY_WARNING_THRESHOLD`) rather than per-paddock, because per-paddock thresholds would add UI without a clear payoff at this scale. The state is calculated server-side in one place so every page agrees.

**Trade-offs.** It is a visual signal only — no notifications, no history of when a paddock crossed the line. Occupancy counts heads, not stocking units, so a paddock of chickens and a paddock of cattle are treated alike.

---

## Assumptions

- **Only active animals occupy capacity.** Animals have a status (`active`, `sold`, `deceased`). Changing an animal away from active removes it from its paddock and records that departure in the movement history, so sold and deceased animals never count against a paddock. Reactivating an animal leaves it unplaced; the user places it explicitly.
- **A paddock is never over capacity.** This is enforced when moving an animal in _and_ when editing a paddock — capacity cannot be set below the current occupancy.
- **An animal can be unplaced.** New animals may be created without a paddock (they show as "Unplaced"), and the dashboard counts them.
- **Single farm, no users.** Authentication is not required by the brief, so the farm pages are public. The starter kit's login/registration remains installed but unused.
- **All dates and times are in the farm's timezone** (`APP_TIMEZONE`, default `UTC`). "Today" for date pickers and the timestamp on a move are interpreted in that timezone, not the browser's, so a farmer and a remote vet entering data see the same dates.
- **Movement history is chronological.** A movement may be back-dated, but not to before the animal's most recent movement — otherwise the stored current paddock and the history would disagree.
- **Tag numbers are unique** across the whole farm and are the primary way a farmer identifies an animal; names are optional.

---

## Important technical decisions

Full reasoning is in [`docs/design.md`](docs/design.md); the short version:

- **Inertia instead of a separate JSON API.** Server-rendered Vue pages keep validation, redirects and flash messages in Laravel and remove a layer of glue code. Wayfinder generates typed route helpers so there are no hard-coded URLs in the frontend.
- **`animals.current_paddock_id` is stored, not derived.** Every list, the dashboard and the capacity check need "where is this animal now"; deriving it from the latest movement would put a correlated subquery on every read. The column is written in exactly one place (`MoveAnimal`) inside the same transaction as the movement row, and a test asserts it always matches the latest movement.
- **One action class for the one non-trivial write.** `MoveAnimal` opens a transaction, locks the animal and the target paddock rows (`lockForUpdate`), checks the rules, inserts history and updates the pointer. It throws a `ValidationException` keyed to the form field, so the UI shows "North Paddock is full (12 of 12)" inline rather than a generic error. `ChangeAnimalStatus` delegates to it for departures.
- **Form Requests own input validation; actions own invariants that depend on database state.** Uniqueness, enums and dates are request rules. "Is there room?" is checked under a lock.
- **PHP backed enums** for species, sex, status and health-record type, exposed to Vue as `{ value, label }` option lists so the frontend never hard-codes them.
- **Thin controllers, JSON resources for Inertia props**, factories with intent-revealing states (`inPaddock()`, `sold()`, `withCapacity()`), and a seeder that goes through the actions rather than inserting rows.

---

## Deliberately left out

- Deleting animals, paddocks or records (the brief asks for add / edit / view; deletion raises history-integrity questions that deserve more than the time budget)
- Recording _who_ moved an animal — there is no authenticated user in scope
- Weight, medication schedules, photos, documents
- Bulk moves ("move these 12 sheep to Top Hill")
- Editing or deleting movements and health records
- Anything beyond simple pagination on the animal list

## What I would improve before production

- Verify the capacity lock under real concurrency on MySQL/Postgres (SQLite ignores `FOR UPDATE`; the transaction still serialises writes, but the behaviour should be tested against the target database)
- Authentication and authorisation, then per-user audit on movements and health records
- Soft deletes and an audit log; `nullOnDelete` on the movement foreign keys is a safety net, not a design
- Stocking-unit-aware capacity rather than head counts
- Reports: CSV export, movement and health history filtered by date range
- Accessibility and mobile pass on the forms; the app is responsive but has not been audited
- Add a cache for dashboard aggregates once the herd is large enough to matter

---

## How AI tools were used

This project was built with Claude Code (Anthropic) as a pair, with me directing and reviewing. The workflow, which is visible in the commit history:

1. **Requirements → design first.** I had Claude read the brief and produce a design document. I made the decisions it surfaced (status model, extra fields, which additional feature, how to treat the starter kit's auth), and the agreed design was committed before any code.
2. **Staged implementation, reviewed per stage.** The build was split into six vertical slices (schema → paddocks → animals + movement engine → move dialog + health → dashboard + seeder → wrap-up). Each stage ended with tests, a type check, a walk-through in the browser, and my review before it was committed. Nothing was committed unreviewed.
3. **Verification caught a real defect.** In stage 5 the seeder — which deliberately goes through `MoveAnimal` and `ChangeAnimalStatus` rather than inserting rows — produced sold animals that were still counted in a paddock. The cause was a stale model instance: `MoveAnimal` re-fetched a locked copy and updated that, leaving the caller's instance out of date, so `ChangeAnimalStatus` saw "not in a paddock" and skipped the departure. The controller path had passed all tests because route-bound models are always fresh. The fix (sync the caller's instance with the locked row; refresh before deciding) landed with two regression tests. This is exactly the kind of bug that reads fine in review and only shows up when the code is exercised from a second call site.
4. **Conventions from the framework, not from memory.** Laravel Boost's guidelines and skills were loaded so generated code follows the installed versions' idioms (Inertia 3 forms, Wayfinder, PHP 8.4 attributes, Pint formatting).

What I did not do: accept the first design without questions, or ship code I had not seen run.
