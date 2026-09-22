<?php

namespace App\Http\Requests;

use App\ApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
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
            'proposal_id' => [
                'nullable',
                'integer',
                Rule::exists('proposals', 'id')->where('user_id', $this->user()?->id),
            ],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
