<?php

namespace App\Sources;

use App\Models\Source;

class SourceManager
{
    /**
     * @var array<string, SourceAdapter>
     */
    private array $adapters = [];

    public function register(SourceAdapter $adapter): void
    {
        $this->adapters[$adapter->driver()] = $adapter;
    }

    public function adapterFor(Source $source): SourceAdapter
    {
        $adapter = $this->adapters[$source->driver] ?? null;

        if ($adapter === null) {
            throw new SourceCollectionException("No source adapter is registered for [{$source->driver}].");
        }

        return $adapter;
    }
}
