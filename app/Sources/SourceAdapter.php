<?php

namespace App\Sources;

use App\Models\Source;

interface SourceAdapter
{
    public function driver(): string;

    /**
     * @throws SourceCollectionException
     */
    public function collect(Source $source): CollectionResult;
}
