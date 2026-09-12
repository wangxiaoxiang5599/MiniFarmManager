<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Capacity and state rules live in the MoveAnimal action, because they
     * depend on locked database state rather than the request alone.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_paddock_id' => ['required', 'integer', Rule::exists('paddocks', 'id')],
            'moved_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'to_paddock_id' => 'paddock',
            'moved_at' => 'date moved',
        ];
    }
}
