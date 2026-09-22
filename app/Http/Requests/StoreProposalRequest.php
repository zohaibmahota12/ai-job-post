<?php

namespace App\Http\Requests;

use App\Models\Proposal;
use App\ProposalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Proposal::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'content' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::enum(ProposalStatus::class)],
        ];
    }
}
