<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StudentRegistration;

class StoreStudentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:student_registrations,email',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[1-9][\d]{0,14}$/',
            ],
            'location' => [
                'required',
                'string',
                'max:200',
            ],
            'experience_level' => [
                'required',
                Rule::in(array_keys(StudentRegistration::EXPERIENCE_LEVELS)),
            ],
            'interests' => [
                'nullable',
                'array',
                'max:8',
            ],
            'interests.*' => [
                'string',
                Rule::in(StudentRegistration::VALID_INTERESTS),
            ],
            'motivation' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.unique' => 'A student with this email address is already registered.',
            'phone.regex' => 'Please enter a valid phone number.',
            'experience_level.in' => 'Please select a valid experience level.',
            'interests.*.in' => 'One or more selected interests are not valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'email' => strtolower(trim($this->email)),
            'phone' => preg_replace('/[^\+\d]/', '', $this->phone),
            'location' => trim($this->location),
        ]);
    }
}
