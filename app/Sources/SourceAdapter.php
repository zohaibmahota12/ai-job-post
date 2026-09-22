<?php

namespace App\Sources;

use App\Models\Source;

interface SourceAdapter
{
    /**
     * Adapter registry key used by SourceManager (for example rss, json_api).
     */
    public function driver(): string;

    /**
     * Source family identifier (rss, json_api, agent_reach, ...).
     */
    public function type(): string;

    /**
     * @throws SourceCollectionException
     */
    public function collect(Source $source): CollectionResult;
}
