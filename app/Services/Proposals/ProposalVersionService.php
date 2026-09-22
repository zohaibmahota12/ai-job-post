<?php

namespace App\Services\Proposals;

use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\ProposalStatus;
use App\ProposalVersionSource;
use Illuminate\Support\Facades\DB;

class ProposalVersionService
{
    public function snapshot(Proposal $proposal, ProposalVersionSource $source): ?ProposalVersion
    {
        if ($proposal->content === null && $proposal->subject === null) {
            return null;
        }

        if (trim((string) $proposal->content) === '' && trim((string) $proposal->subject) === '') {
            return null;
        }

        return $proposal->versions()->create([
            'content' => $proposal->content,
            'subject' => $proposal->subject,
            'source' => $source,
        ]);
    }

    /**
     * @param  array{
     *     content: string|null,
     *     subject?: string|null,
     *     generated_by_ai?: bool,
     *     ai_provider?: string|null,
     *     ai_model?: string|null,
     *     ai_metadata?: array<string, mixed>|null,
     *     status?: ProposalStatus|null
     * }  $attributes
     */
    public function replaceActiveDraft(
        Proposal $proposal,
        array $attributes,
        ProposalVersionSource $versionSource,
        bool $preservePrevious = true,
    ): Proposal {
        return DB::transaction(function () use ($proposal, $attributes, $versionSource, $preservePrevious): Proposal {
            $proposal->refresh();

            if ($preservePrevious) {
                $this->snapshot($proposal, $this->sourceForExisting($proposal));
            }

            $proposal->forceFill([
                'content' => $attributes['content'] ?? $proposal->content,
                'subject' => array_key_exists('subject', $attributes) ? $attributes['subject'] : $proposal->subject,
                'status' => $attributes['status'] ?? ProposalStatus::Draft,
                'generated_by_ai' => $attributes['generated_by_ai'] ?? $proposal->generated_by_ai,
                'ai_provider' => array_key_exists('ai_provider', $attributes) ? $attributes['ai_provider'] : $proposal->ai_provider,
                'ai_model' => array_key_exists('ai_model', $attributes) ? $attributes['ai_model'] : $proposal->ai_model,
                'ai_metadata' => array_key_exists('ai_metadata', $attributes) ? $attributes['ai_metadata'] : $proposal->ai_metadata,
            ])->save();

            $proposal->versions()->create([
                'content' => $proposal->content,
                'subject' => $proposal->subject,
                'source' => $versionSource,
            ]);

            return $proposal->fresh(['versions', 'opportunity']);
        });
    }

    public function restore(Proposal $proposal, ProposalVersion $version): Proposal
    {
        abort_unless($version->proposal_id === $proposal->id, 404);

        return $this->replaceActiveDraft($proposal, [
            'content' => $version->content,
            'subject' => $version->subject,
            'status' => ProposalStatus::Draft,
            'generated_by_ai' => $version->source === ProposalVersionSource::Ai
                || $version->source === ProposalVersionSource::Regenerate,
        ], ProposalVersionSource::User);
    }

    private function sourceForExisting(Proposal $proposal): ProposalVersionSource
    {
        if ($proposal->generated_by_ai) {
            return ProposalVersionSource::Ai;
        }

        return ProposalVersionSource::User;
    }
}
