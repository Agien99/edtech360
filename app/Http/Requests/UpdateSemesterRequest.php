<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'academic_sessions.manage'
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'number' => [
                'required',
                'integer',
                Rule::in([1, 2, 3]),
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
}