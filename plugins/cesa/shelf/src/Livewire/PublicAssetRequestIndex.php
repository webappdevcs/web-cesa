<?php

namespace Cesa\Shelf\Livewire;

use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicAssetRequestIndex extends SimplePage
{
    protected static string $layout = 'shelf::layouts.form';

    protected string $view = 'shelf::livewire.public-asset-request-index';

    public function mount(): void
    {
        if (! Package::isPluginInstalled('shelf')) {
            abort(404);
        }
    }

    protected function getViewData(): array
    {
        return [
            'types' => [
                'pengadaan-aset' => [
                    'label'       => 'Pengadaan Aset',
                    'description' => 'Ajukan kebutuhan aset baru untuk mendukung pekerjaan atau operasional.',
                ],
                'perbaikan-aset' => [
                    'label'       => 'Perbaikan Aset',
                    'description' => 'Laporkan aset yang perlu diperbaiki agar bisa ditindaklanjuti tim terkait.',
                ],
                'penarikan-aset' => [
                    'label'       => 'Penarikan Aset',
                    'description' => 'Ajukan penarikan aset yang sudah tidak digunakan atau perlu dikembalikan.',
                ],
            ],
        ];
    }
}
