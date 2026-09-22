<?php

namespace App\Services\Proposals;

use App\ApplicationStatus;
use App\Models\Application;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\User;
use App\ProposalStatus;
use App\ProposalVersionSource;
use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Ai\GeneratedProposalParser;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProposalGenerator
{
    public function __construct(
        private AiManager $ai,
        private ProposalContextBuilder $contextBuilder,
        private ProposalPromptBuilder $promptBuilder,
        private GeneratedProposalParser $parser,
        private ProposalVersionService $versions,
        private NotificationService $notifications,
    ) {}

    public function generate(User $user, Opportunity $opportunity, ?Proposal $existing = null): Proposal
    {
        return $this->run($user, $opportunity, $existing, regenerate: false);
    }

    public function regenerate(User $user, Proposal $proposal): Proposal
    {
        $proposal->loadMissing('opportunity');

        return $this->run($user, $proposal->opportunity, $proposal, regenerate: true);
    }

    private function run(User $user, Opportunity $opportunity, ?Proposal $existing, bool $regenerate): Proposal
    {
        $provider = $this->ai->provider();

        $proposal = $existing ?? Proposal::query()
            ->where('user_id', $user->id)
            ->where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();

        if ($proposal === null) {
            $proposal = new Proposal([
                'user_id' => $user->id,
                'opportunity_id' => $opportunity->id,
                'status' => ProposalStatus::Draft,
                'content' => null,
                'subject' => null,
                'generated_by_ai' => false,
            ]);
            $proposal->save();
        }

        $beforeContent = $proposal->content;
        $beforeSubject = $proposal->subject;
        $beforeMeta = [
            'generated_by_ai' => $proposal->generated_by_ai,
            'ai_provider' => $proposal->ai_provider,
            'ai_model' => $proposal->ai_model,
            'ai_metadata' => $proposal->ai_metadata,
            'status' => $proposal->status,
        ];

        try {
            $context = $this->contextBuilder->build($user, $opportunity);
            $messages = $this->promptBuilder->messages($context);
            $chat = $provider->chat($messages, json: true);
            $parsed = $this->parser->parse($chat->content);

            $updated = $this->versions->replaceActiveDraft(
                $proposal,
                [
                    'content' => $parsed->proposal,
                    'subject' => $parsed->subject,
                    'status' => ProposalStatus::Draft,
                    'generated_by_ai' => true,
                    'ai_provider' => $chat->provider,
                    'ai_model' => $chat->model,
                    'ai_metadata' => [
                        'key_points' => $parsed->keyPoints,
                        'prompt_tokens' => $chat->promptTokens,
                        'completion_tokens' => $chat->completionTokens,
                        'generated_at' => now()->toIso8601String(),
                        'regenerated' => $regenerate,
                    ],
                ],
                $regenerate ? ProposalVersionSource::Regenerate : ProposalVersionSource::Ai,
                preservePrevious: $regenerate || filled($beforeContent) || filled($beforeSubject),
            );

            $this->syncApplicationDraft($user, $opportunity, $updated);

            $this->notifications->notify(
                $user,
                'proposal.generated',
                $regenerate ? 'Proposal regenerated' : 'AI proposal generated',
                'A draft proposal is ready for your review. Nothing was submitted externally.',
                [
                    'proposal_id' => $updated->id,
                    'opportunity_id' => $opportunity->id,
                ],
            );

            return $updated;
        } catch (AiException $exception) {
            $this->restoreUntouched($proposal, $beforeContent, $beforeSubject, $beforeMeta, wasNew: $existing === null && ! filled($beforeContent) && ! filled($beforeSubject));

            $this->notifications->notify(
                $user,
                'proposal.generation_failed',
                'Proposal generation failed',
                $exception->userMessage(),
                [
                    'proposal_id' => $proposal->exists ? $proposal->id : null,
                    'opportunity_id' => $opportunity->id,
                ],
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->restoreUntouched($proposal, $beforeContent, $beforeSubject, $beforeMeta, wasNew: $existing === null && ! filled($beforeContent) && ! filled($beforeSubject));

            Log::warning('Unexpected proposal generation failure.', [
                'user_id' => $user->id,
                'opportunity_id' => $opportunity->id,
                'proposal_id' => $proposal->id,
                'error' => $exception->getMessage(),
            ]);

            $this->notifications->notify(
                $user,
                'proposal.generation_failed',
                'Proposal generation failed',
                'AI proposal generation failed. You can still create and edit a proposal manually.',
                [
                    'proposal_id' => $proposal->exists ? $proposal->id : null,
                    'opportunity_id' => $opportunity->id,
                ],
            );

            throw $exception;
        }
    }

    /**
     * @param  array{generated_by_ai: bool, ai_provider: ?string, ai_model: ?string, ai_metadata: mixed, status: ProposalStatus}  $beforeMeta
     */
    private function restoreUntouched(
        Proposal $proposal,
        ?string $beforeContent,
        ?string $beforeSubject,
        array $beforeMeta,
        bool $wasNew,
    ): void {
        if ($wasNew && $proposal->exists) {
            $proposal->delete();

            return;
        }

        if (! $proposal->exists) {
            return;
        }

        $proposal->forceFill([
            'content' => $beforeContent,
            'subject' => $beforeSubject,
            'generated_by_ai' => $beforeMeta['generated_by_ai'],
            'ai_provider' => $beforeMeta['ai_provider'],
            'ai_model' => $beforeMeta['ai_model'],
            'ai_metadata' => $beforeMeta['ai_metadata'],
            'status' => $beforeMeta['status'],
        ])->save();
    }

    private function syncApplicationDraft(User $user, Opportunity $opportunity, Proposal $proposal): void
    {
        $application = Application::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'opportunity_id' => $opportunity->id,
            ],
            [
                'status' => ApplicationStatus::ProposalDraft,
                'proposal_id' => $proposal->id,
            ],
        );

        if ($application->proposal_id === null) {
            $application->forceFill(['proposal_id' => $proposal->id])->save();
        }

        if (in_array($application->status, [ApplicationStatus::New, ApplicationStatus::Saved], true)) {
            $application->forceFill(['status' => ApplicationStatus::ProposalDraft])->save();
        }
    }
}
