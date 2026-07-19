<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\ExitClearance\Models\Request;

class ExitClearanceLifecycleAdapter
{
    public function __construct(private readonly EmployeeLifecycleBridge $bridge) {}

    public function handle(int $requestId): void
    {
        $request = Request::query()->findOrFail($requestId);

        $this->bridge->startOffboardingFromExit([
            'name'           => $request->name,
            'email'          => $request->email,
            'departure_date' => $request->departure_date?->toDateString(),
            'reason'         => $request->reason,
            'form_uid'       => $request->form_uid,
        ], $requestId);
    }
}
