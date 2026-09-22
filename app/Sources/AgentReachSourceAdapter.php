<?php

namespace App\Sources;

use App\Models\Source;

/**
 * Placeholder for Phase 2. This class does not call Agent Reach and does not invent listings.
 */
class AgentReachSourceAdapter implements SourceAdapter
{
    public function driver(): string
    {
        return 'agent_reach';
    }

    public function collect(Source $source): CollectionResult
    {
        throw new SourceCollectionException(
            'Agent Reach collection is not implemented. See docs/AGENT_REACH.md. This adapter does not invent opportunity data.'
        );
    }
}
