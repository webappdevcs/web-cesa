<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Models\FormTransfer;
use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicTransferRequestIndex extends SimplePage
{
    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-request-index';

    public function mount(): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.index.heading');
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.index.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        return [
            'formTransfers' => $this->getActiveFormTransfers(),
        ];
    }

    protected function getActiveFormTransfers()
    {
        return FormTransfer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
