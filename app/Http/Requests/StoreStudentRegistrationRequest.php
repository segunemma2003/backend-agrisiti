<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StudentRegistration;
use Carbon\Carbon;

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
            'age' => [
                'required',
                'integer',
                'min:5',
                'max:100',
            ],
            'date_of_birth' => [
                'required',
                'date',
                'before:today',
                'after:1900-01-01',
            ],
            'school_name' => [
                'required',
                'string',
                'max:200',
            ],
            'parent_name' => [
                'required',
                'string',
                'max:200',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'parent_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[1-9][\d]{0,14}$/',
            ],
            'parent_email' => [
                'required',
                'email:rfc,dns',
                'max:255',
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
            'parent_name.regex' => 'The parent name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.unique' => 'A student with this email address is already registered.',
            'phone.regex' => 'Please enter a valid phone number.',
            'parent_phone.regex' => 'Please enter a valid parent phone number.',
            'age.min' => 'Student must be at least 5 years old.',
            'age.max' => 'Student age cannot exceed 100 years.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'date_of_birth.after' => 'Date of birth must be after 1900.',
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
            'parent_name' => trim($this->parent_name),
            'parent_phone' => preg_replace('/[^\+\d]/', '', $this->parent_phone),
            'parent_email' => strtolower(trim($this->parent_email)),
            'school_name' => trim($this->school_name),
            'location' => trim($this->location),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate age matches date of birth
            if ($this->filled(['age', 'date_of_birth'])) {
                $calculatedAge = Carbon::parse($this->date_of_birth)->age;
                if (abs($calculatedAge - $this->age) > 1) {
                    $validator->errors()->add('age', 'The age does not match the date of birth.');
                }
            }
        });
    }
}
