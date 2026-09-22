<?php

namespace App\Sources;

use App\Models\Source;

/**
 * Optional external integration placeholder. Not part of the core collection pipeline.
 *
 * Agent Reach is not a generic jobs API and may require local/browser infrastructure
 * incompatible with shared hosting. See docs/AGENT_REACH.md.
 */
class AgentReachSourceAdapter implements SourceAdapter
{
    public function driver(): string
    {
        return 'agent_reach';
    }

    public function type(): string
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
