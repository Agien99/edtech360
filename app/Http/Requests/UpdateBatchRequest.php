<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('batches.manage') ?? false;
    }

    public function rules(): array
    {
        $batch = $this->route('batch');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('batches', 'code')
                    ->ignore($batch),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'intake_year' => [
                'required',
                'integer',
                'between:2000,2100',
            ],

            'start_date' => [
                'nullable',
                'required_with:expected_end_date',
                'date_format:Y-m-d',
            ],

            'expected_end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' =>
                'This batch code is already registered.',

            'code.regex' =>
                'The batch code may only contain letters, numbers, hyphens, and underscores.',

            'expected_end_date.after_or_equal' =>
                'The expected end date cannot be earlier than the start date.',

            'start_date.required_with' =>
                'A start date is required when an expected end date is provided.',
        ];
    }
}