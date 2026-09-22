<?php

namespace App\Services\Ai\Dto;

readonly class GeneratedProposalContent
{
    /**
     * @param  list<string>  $keyPoints
     */
    public function __construct(
        public string $proposal,
        public ?string $subject = null,
        public array $keyPoints = [],
    ) {}
}
