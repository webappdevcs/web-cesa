<?php

namespace Cesa\Shelf\Livewire;

use Cesa\Shelf\Services\PublicAssetRequestService;
use Cesa\Shelf\Support\ShelfStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;
use Webkul\PluginManager\Package;

class PublicAssetRequestForm extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'shelf::layouts.form';

    protected string $view = 'shelf::livewire.public-asset-request-form';

    public ?array $data = [];

    public array $requestType = [];

    public string $slug;

    public Collection $divisions;

    protected PublicAssetRequestService $publicAssetRequestService;

    public function boot(PublicAssetRequestService $publicAssetRequestService): void
    {
        $this->publicAssetRequestService = $publicAssetRequestService;
    }

    public function mount(string $type): void
    {
        if (! Package::isPluginInstalled('shelf')) {
            abort(404);
        }

        $requestType = $this->requestTypes()[$type] ?? null;

        abort_if($requestType === null, 404);

        $this->slug = $type;
        $this->requestType = $requestType;
        $this->divisions = $this->getDivisionOptions($requestType['value']);

        $this->form->fill([
            'qty' => 1,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('requester_name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nama lengkap Anda'),
                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('email@perusahaan.com')
                    ->helperText('Konfirmasi dan progress pengajuan akan direferensikan ke email ini.'),
                TextInput::make('division')
                    ->label('Divisi')
                    ->required()
                    ->maxLength(255)
                    ->datalist($this->divisions->mapWithKeys(fn (string $division): array => [$division => $division])->all())
                    ->placeholder('Contoh: Finance, Operations')
                    ->helperText($this->divisions->isNotEmpty()
                        ? 'Gunakan nama divisi sesuai jalur approval yang tersedia.'
                        : 'Master divisi belum tersedia, jadi input masih manual.'),
                TextInput::make('placement')
                    ->label('Penempatan')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Central Jakarta'),
                TextInput::make('item_name')
                    ->label('Nama Barang')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nama barang yang diajukan')
                    ->helperText(in_array($this->slug, ['perbaikan-aset', 'penarikan-aset'], true)
                        ? 'Sistem akan mencoba mencocokkan aset dari nama pemohon dan nama barang.'
                        : null),
                TextInput::make('qty')
                    ->label('Qty')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                FileUpload::make('attachment_path')
                    ->label('Lampiran')
                    ->disk(ShelfStorage::disk())
                    ->directory('shelf/asset-requests/tmp')
                    ->visibility('private')
                    ->fetchFileInformation(false)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'image/webp',
                        'image/gif',
                        'image/bmp',
                    ])
                    ->storeFileNamesIn('attachment_original_name')
                    ->maxSize(5120)
                    ->helperText('Opsional. Maksimal 5 MB. Format PDF atau gambar.')
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function submit(): mixed
    {
        try {
            $assetRequest = $this->publicAssetRequestService->submit(
                $this->slug,
                $this->form->getState(),
            );

            Notification::make()
                ->title('Pengajuan berhasil dikirim.')
                ->success()
                ->send();

            return redirect()->route('asset-requests.progress', [
                'uuid' => $assetRequest->uuid,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $errorKey = str_starts_with($key, 'data.') ? $key : 'data.'.$key;

                foreach ($messages as $message) {
                    $this->addError($errorKey, $message);
                }
            }

            Notification::make()
                ->title('Periksa kembali data pengajuan.')
                ->warning()
                ->send();

            return null;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Pengajuan tidak dapat diproses saat ini.')
                ->body('Silakan coba lagi beberapa saat lagi.')
                ->danger()
                ->send();

            return null;
        }
    }

    public function getHeading(): string
    {
        return $this->requestType['label'];
    }

    public function getSubheading(): string
    {
        return 'Lengkapi data berikut untuk mengirim pengajuan aset.';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSubmitAction(),
        ];
    }

    protected function getSubmitAction(): Action
    {
        return Action::make('submit')
            ->label('Submit Request')
            ->extraAttributes([
                'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
            ], merge: true)
            ->submit('submit');
    }

    protected function getViewData(): array
    {
        return [
            'slug'        => $this->slug,
            'requestType' => $this->requestType,
            'divisions'   => $this->divisions,
        ];
    }

    private function requestTypes(): array
    {
        return [
            'pengadaan-aset' => [
                'value' => 'pengadaan_aset',
                'label' => 'Pengadaan Aset',
            ],
            'perbaikan-aset' => [
                'value' => 'perbaikan_aset',
                'label' => 'Perbaikan Aset',
            ],
            'penarikan-aset' => [
                'value' => 'penarikan_aset',
                'label' => 'Penarikan Aset',
            ],
        ];
    }

    private function getDivisionOptions(string $requestType): Collection
    {
        return $this->publicAssetRequestService->getDivisionOptions($requestType);
    }
}
