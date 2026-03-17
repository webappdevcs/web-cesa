<?php

namespace Cesa\Shelf\Filament\Resources\AssetRequestResource\Pages;

use Cesa\Shelf\Filament\Resources\AssetRequestResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ViewAssetRequest extends ViewRecord
{
    protected static string $resource = AssetRequestResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('shelf::filament.resources.asset-request.pages.view-asset-request.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        return "ID {$record->id} / {$record->requester_name} / {$record->item_name}";
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
