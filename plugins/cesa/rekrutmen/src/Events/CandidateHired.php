<?php

namespace Cesa\Rekrutmen\Events;

class CandidateHired
{
    public function __construct(
        public readonly int $jobApplicationId,
        public readonly ?int $performedBy = null,
    ) {}
}
