<?php

namespace App\Services\Applications;

use App\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;

class ApplicationWorkflow
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * @param  array{
     *     status?: ApplicationStatus|null,
     *     notes?: string|null,
     *     proposal_id?: int|null,
     *     contact_name?: string|null,
     *     follow_up_at?: string|null,
     *     external_url?: string|null,
     *     applied_at?: mixed
     * }  $attributes
     */
    public function update(Application $application, User $actor, array $attributes, ?string $note = null): Application
    {
        return DB::transaction(function () use ($application, $actor, $attributes, $note): Application {
            $oldStatus = $application->status;
            $newStatus = $attributes['status'] ?? $oldStatus;

            $appliedAt = $application->applied_at;

            if ($newStatus === ApplicationStatus::Applied && $appliedAt === null) {
                $appliedAt = $attributes['applied_at'] ?? now();
            }

            $application->fill([
                'proposal_id' => array_key_exists('proposal_id', $attributes)
                    ? $attributes['proposal_id']
                    : $application->proposal_id,
                'status' => $newStatus,
                'notes' => array_key_exists('notes', $attributes) ? $attributes['notes'] : $application->notes,
                'contact_name' => array_key_exists('contact_name', $attributes)
                    ? $attributes['contact_name']
                    : $application->contact_name,
                'follow_up_at' => array_key_exists('follow_up_at', $attributes)
                    ? $attributes['follow_up_at']
                    : $application->follow_up_at,
                'external_url' => array_key_exists('external_url', $attributes)
                    ? $attributes['external_url']
                    : $application->external_url,
                'applied_at' => $appliedAt,
            ])->save();

            if ($oldStatus !== $newStatus) {
                $this->recordHistory($application, $actor, $oldStatus, $newStatus, $note);
                $this->notifyStatusChange($application, $newStatus);
            }

            return $application->fresh(['opportunity', 'proposal', 'statusHistories']);
        });
    }

    public function markApplied(Application $application, User $actor, ?string $note = null): Application
    {
        return $this->update($application, $actor, [
            'status' => ApplicationStatus::Applied,
            'applied_at' => $application->applied_at ?? now(),
        ], $note ?? 'Marked as applied manually.');
    }

    private function recordHistory(
        Application $application,
        User $actor,
        ApplicationStatus $oldStatus,
        ApplicationStatus $newStatus,
        ?string $note,
    ): ApplicationStatusHistory {
        return $application->statusHistories()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $actor->id,
            'note' => $note,
        ]);
    }

    private function notifyStatusChange(Application $application, ApplicationStatus $status): void
    {
        $map = [
            ApplicationStatus::Applied->value => ['application.applied', 'Application marked applied', 'You recorded that you applied externally. Opportunity Hunter did not submit anything for you.'],
            ApplicationStatus::Interview->value => ['application.interview', 'Application moved to interview', 'You updated this application to interview.'],
            ApplicationStatus::Hired->value => ['application.hired', 'Application marked hired', 'You marked this application as hired.'],
            ApplicationStatus::Rejected->value => ['application.rejected', 'Application marked rejected', 'You marked this application as rejected.'],
        ];

        if (! isset($map[$status->value])) {
            return;
        }

        [$type, $title, $body] = $map[$status->value];

        $this->notifications->notify(
            $application->user,
            $type,
            $title,
            $body,
            [
                'application_id' => $application->id,
                'opportunity_id' => $application->opportunity_id,
            ],
        );
    }
}
