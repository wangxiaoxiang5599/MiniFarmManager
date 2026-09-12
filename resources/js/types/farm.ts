export type SelectOption = {
    value: string;
    label: string;
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
