<?php

namespace App\Http\Requests;

use App\JobType;
use App\RemotePreference;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'location' => ['nullable', 'string', 'max:120'],
            'preferred_job_type' => ['required', Rule::enum(JobType::class)],
            'remote_preference' => ['required', Rule::enum(RemotePreference::class)],
            'minimum_budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'preferred_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'excluded_keywords' => ['nullable', 'string', 'max:500'],
        ];
    }
}
