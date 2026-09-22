<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_enabled')) {
            $this->merge([
                'is_enabled' => filter_var($this->input('is_enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
