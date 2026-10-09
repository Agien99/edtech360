<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('academic_sessions.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('academic_sessions', 'name'),
            ],
            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This academic session already exists.',
            'end_date.after_or_equal' =>
                'The end date must not be earlier than the start date.',
        ];
    }
}