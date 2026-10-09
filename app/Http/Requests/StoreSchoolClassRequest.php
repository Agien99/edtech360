<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolClassRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('classes.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')->whereIn('status', ['planned', 'active'])],
            'batch_id' => ['required', 'integer', Rule::exists('batches', 'id')->whereNull('deleted_at')->where('status', 'active')],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('classes', 'code')->where('academic_session_id', $this->input('academic_session_id'))],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_session_id.exists' => 'Select a valid, non-closed academic session.',
            'batch_id.exists' => 'Select an active student batch.',
            'code.unique' => 'This class code already exists in the selected session.',
            'code.regex' => 'Class codes may contain only letters, numbers, hyphens and underscores.',
        ];
    }
}
