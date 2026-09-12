export type SelectOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

export type OccupancyState = 'ok' | 'warning' | 'full';

export type Paddock = {
    id: number;
    name: string;
    capacity: number;
    notes: string | null;
    occupancy: number;
    remaining_capacity: number;
    occupancy_ratio: number;
    occupancy_state: OccupancyState;
    has_room: boolean;
};

export type AnimalStatus = 'active' | 'sold' | 'deceased';

export type AnimalSummary = {
    id: number;
    tag_number: string;
    name: string | null;
    display_name: string;
    species: string;
    species_label: string;
    sex: string;
    sex_label: string;
    breed: string | null;
    date_of_birth: string;
    age: string;
    status: AnimalStatus;
    status_label: string;
    current_paddock?: { id: number; name: string } | null;
};

export type Animal = AnimalSummary & {
    notes: string | null;
    is_active: boolean;
    created_at: string | null;
};

export type PaddockRef = { id: number; name: string };

export type AnimalMovement = {
    id: number;
    moved_at: string;
    moved_at_label: string;
    from_paddock: PaddockRef | null;
    to_paddock: PaddockRef | null;
    notes: string | null;
    animal?: { id: number; tag_number: string; display_name: string };
};

export type AnimalFormOptions = {
    species: SelectOption[];
    sexes: SelectOption[];
    statuses: SelectOption[];
    paddocks: Paddock[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
};
