<?php

namespace App\Http\Requests;

use App\Enums\HealthRecordType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthRecordRequest extends FormRequest
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
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::enum(HealthRecordType::class)],
            'description' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recorded_on' => 'date',
        ];
    }
}
