<?php

namespace Cesa\ExitClearance\Events;

class ExitClearanceApproved
{
    public function __construct(public readonly int $requestId) {}
}
