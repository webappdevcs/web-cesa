<?php

namespace Cesa\Shelf\Livewire;

use Cesa\Shelf\Models\AssetRequest;
use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicAssetRequestProgressPage extends SimplePage
{
    protected static string $layout = 'shelf::layouts.form';

    protected string $view = 'shelf::livewire.public-asset-request-progress-page';

    public AssetRequest $assetRequest;

    public string $requestTypeLabel;

    public function mount(string $uuid): void
    {
        if (! Package::isPluginInstalled('shelf')) {
            abort(404);
        }

        $this->assetRequest = AssetRequest::query()
            ->where('uuid', $uuid)
            ->with('approvals')
            ->firstOrFail();

        $this->requestTypeLabel = AssetRequest::getRequestTypeLabel($this->assetRequest->request_type);
    }
}
