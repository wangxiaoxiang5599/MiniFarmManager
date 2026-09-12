<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Enums\Species;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tag_number' => ['required', 'string', 'max:50', Rule::unique('animals', 'tag_number')],
            'name' => ['nullable', 'string', 'max:100'],
            'species' => ['required', Rule::enum(Species::class)],
            'sex' => ['required', Rule::enum(Sex::class)],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'breed' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'paddock_id' => ['nullable', 'integer', Rule::exists('paddocks', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'paddock_id' => 'paddock',
        ];
    }
}
