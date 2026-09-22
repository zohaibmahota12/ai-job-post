<?php

namespace App\Sources;

final class CollectionResult
{
    /**
     * @param  list<RawOpportunity>  $opportunities
     */
    public function __construct(public array $opportunities) {}
}
