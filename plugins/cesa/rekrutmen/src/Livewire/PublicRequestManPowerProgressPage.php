<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Pages\SimplePage;

class PublicRequestManPowerProgressPage extends SimplePage
{
    protected static string $layout = 'rekrutmen::layouts.form';

    protected string $view = 'rekrutmen::livewire.public-man-power-progress-page';

    public RequestManPower $requestManPower;

    public function mount(string $response): void
    {
        $this->requestManPower = RequestManPower::query()
            ->where('status_response_id', $response)
            ->firstOrFail();
    }

    public function getHeading(): string
    {
        return __('rekrutmen::app.public_progress.heading');
    }

    public function getSubheading(): string
    {
        return __('rekrutmen::app.public_progress.subheading');
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
