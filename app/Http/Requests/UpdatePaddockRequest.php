<?php

namespace App\Http\Requests;

use App\Models\Paddock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaddockRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', Rule::unique('paddocks', 'name')->ignore($this->paddock())],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Capacity may never drop below the number of animals already inside,
     * otherwise the "never over capacity" invariant would silently break.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('capacity')) {
                    return;
                }

                $occupancy = $this->paddock()->occupancy;

                if ((int) $this->input('capacity') < $occupancy) {
                    $validator->errors()->add(
                        'capacity',
                        "Capacity cannot be lower than the {$occupancy} animals currently in this paddock.",
                    );
                }
            },
        ];
    }

    private function paddock(): Paddock
    {
        return $this->route('paddock');
    }
}
