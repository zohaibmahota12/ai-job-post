<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\OpportunityStatus;
use Illuminate\Support\Carbon;

class OpportunityLifecycle
{
    /**
     * Mark open opportunities with a known past deadline as expired.
     * Does not invent deadlines and does not delete records.
     */
    public function expirePastDeadlines(?Carbon $now = null): int
    {
        $now ??= now();

        return Opportunity::query()
            ->where('status', OpportunityStatus::Open)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', $now)
            ->update(['status' => OpportunityStatus::Expired]);
    }

    /**
     * Resolve listing status from an optional explicit source signal.
     */
    public function statusFromListingSignal(?string $signal, ?string $deadlineAt = null, ?Carbon $now = null): OpportunityStatus
    {
        $now ??= now();
        $normalized = is_string($signal) ? strtolower(trim($signal)) : '';

        if (in_array($normalized, ['closed', 'filled', 'unavailable', 'inactive'], true)) {
            return OpportunityStatus::Closed;
        }

        if (in_array($normalized, ['expired', 'deadline_passed'], true)) {
            return OpportunityStatus::Expired;
        }

        if ($deadlineAt !== null) {
            try {
                if (Carbon::parse($deadlineAt)->lt($now)) {
                    return OpportunityStatus::Expired;
                }
            } catch (\Throwable) {
                // Keep open when the deadline cannot be parsed.
            }
        }

        return OpportunityStatus::Open;
    }
}
