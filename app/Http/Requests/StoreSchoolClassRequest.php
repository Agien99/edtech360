<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolClassRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['code','name'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => $key === 'code' ? strtoupper(trim($this->input($key))) : trim($this->input($key))]);
            }
        }
    }
    public function authorize(): bool { return $this->user()?->can('classes.create') ?? false; }
    public function rules(): array
    {
        return [
            'academic_session_id' => ['required','integer',
                Rule::exists('academic_sessions','id')->whereNull('deleted_at')->whereIn('status',['planned','active'])],
            'code' => ['required','string','max:30','regex:/^[A-Z0-9_-]+$/',
                Rule::unique('classes','code')->where('academic_session_id',$this->input('academic_session_id'))],
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string','max:2000'],
        ];
    }
}
