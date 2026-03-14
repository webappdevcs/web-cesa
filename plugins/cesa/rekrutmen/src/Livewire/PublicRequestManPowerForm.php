<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublicRequestManPowerForm extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'rekrutmen::layouts.form';

    protected string $view = 'rekrutmen::livewire.public-man-power-request-form';

    public ?array $data = [];

    public ?array $recentSubmission = null;

    protected bool $recaptchaEnabled = false;

    protected ?string $recaptchaSiteKey = null;

    protected ?string $recaptchaSecretKey = null;

    protected ?string $recaptchaAction = null;

    protected float $recaptchaScoreThreshold = 0.0;

    protected int $recaptchaTimeout = 5;

    public function mount(): void
    {
        $recaptcha = config('rekrutmen.security.recaptcha', []);

        $this->recaptchaSiteKey = Arr::get($recaptcha, 'site_key');
        $this->recaptchaSecretKey = Arr::get($recaptcha, 'secret_key');
        $this->recaptchaAction = Arr::get($recaptcha, 'action', 'request_man_power');
        $this->recaptchaScoreThreshold = (float) Arr::get($recaptcha, 'score_threshold', 0.0);
        $this->recaptchaTimeout = (int) Arr::get($recaptcha, 'timeout', 5);

        $this->recaptchaEnabled = (bool) Arr::get($recaptcha, 'enabled', false)
            && filled($this->recaptchaSiteKey)
            && filled($this->recaptchaSecretKey);

        if ($this->recaptchaEnabled) {
            $this->data['recaptcha_token'] = null;
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_pengaju')
                    ->label(__('rekrutmen::app.public_request_form.fields.nama_pengaju'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.nama_pengaju')),
                TextInput::make('posisi_pengaju')
                    ->label(__('rekrutmen::app.public_request_form.fields.posisi_pengaju'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.posisi_pengaju')),
                TextInput::make('email_address')
                    ->label(__('rekrutmen::app.public_request_form.fields.email_address'))
                    ->email()
                    ->nullable()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.email_address')),
                DatePicker::make('tanggal_pengajuan')
                    ->label(__('rekrutmen::app.public_request_form.fields.tanggal_pengajuan'))
                    ->required()
                    ->default(now()),
                TextInput::make('divisi')
                    ->label(__('rekrutmen::app.public_request_form.fields.divisi'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.divisi')),
                TextInput::make('badan_usaha')
                    ->label(__('rekrutmen::app.public_request_form.fields.badan_usaha'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.badan_usaha')),
                TextInput::make('posisi_dibutuhkan')
                    ->label(__('rekrutmen::app.public_request_form.fields.posisi_dibutuhkan'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.posisi_dibutuhkan')),
                TextInput::make('lokasi_penempatan')
                    ->label(__('rekrutmen::app.public_request_form.fields.lokasi_penempatan'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.lokasi_penempatan')),
                Select::make('status_kebutuhan')
                    ->label(__('rekrutmen::app.public_request_form.fields.status_kebutuhan'))
                    ->required()
                    ->options(StatusKebutuhan::class)
                    ->default(StatusKebutuhan::NEW_HIRING)
                    ->live(),
                TextInput::make('nama_karyawan_replacement')
                    ->label(__('rekrutmen::app.public_request_form.fields.nama_karyawan_replacement'))
                    ->maxLength(255)
                    ->nullable()
                    ->required(fn (callable $get) => $this->isReplacementStatus($get('status_kebutuhan')))
                    ->helperText(__('rekrutmen::app.public_request_form.helper_texts.nama_karyawan_replacement'))
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.nama_karyawan_replacement'))
                    ->visible(fn (callable $get) => $this->isReplacementStatus($get('status_kebutuhan'))),
                Select::make('level_pekerjaan')
                    ->label(__('rekrutmen::app.public_request_form.fields.level_pekerjaan'))
                    ->required()
                    ->options(RequestManPower::getTranslatedLevelPekerjaanOptions()),
                TextInput::make('jumlah_karyawan_dibutuhkan')
                    ->label(__('rekrutmen::app.public_request_form.fields.jumlah_karyawan_dibutuhkan'))
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
                DatePicker::make('estimasi_tanggal_join')
                    ->label(__('rekrutmen::app.public_request_form.fields.estimasi_tanggal_join'))
                    ->required(),
                Textarea::make('requirements_kualifikasi')
                    ->label(__('rekrutmen::app.public_request_form.fields.requirements_kualifikasi'))
                    ->required()
                    ->rows(6)
                    ->columnSpanFull()
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.requirements_kualifikasi')),
                Textarea::make('job_description')
                    ->label(__('rekrutmen::app.public_request_form.fields.job_description'))
                    ->required()
                    ->rows(6)
                    ->columnSpanFull()
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.job_description')),
                Textarea::make('keterangan')
                    ->label(__('rekrutmen::app.public_request_form.fields.keterangan'))
                    ->nullable()
                    ->columnSpanFull()
                    ->placeholder(__('rekrutmen::app.public_request_form.placeholders.keterangan')),
                Hidden::make('recaptcha_token')
                    ->default('')
                    ->dehydrated(),
            ])
            ->statePath('data');
    }

    public function submit(): mixed
    {
        $this->dispatch('form-processing-started');

        $state = $this->form->getState();

        if ($this->recaptchaEnabled && ! $this->verifyRecaptchaToken($state)) {
            $this->handleValidationError();
            $this->dispatch('form-processing-finished');

            return null;
        }

        if (
            $this->isReplacementStatus($state['status_kebutuhan'] ?? null)
            && blank($state['nama_karyawan_replacement'] ?? null)
        ) {
            $this->addError('data.nama_karyawan_replacement', __('rekrutmen::app.public_request_form.errors.nama_karyawan_replacement_required'));
            $this->handleValidationError();
            $this->dispatch('form-processing-finished');

            return null;
        }

        try {
            $rmp = RequestManPower::create([
                ...Arr::except($state, ['recaptcha_token']),
            ]);

            $rmp->sendSubmittedNotification();

            $this->recentSubmission = [
                'id'                 => $rmp->getKey(),
                'status_response_id' => $rmp->status_response_id,
                'progress_url'       => $rmp->getPublicProgressUrl(),
                'posisi_dibutuhkan'  => $rmp->posisi_dibutuhkan,
                'nama_pengaju'       => $rmp->nama_pengaju,
                'status_kebutuhan'   => $rmp->status_kebutuhan->getLabel(),
                'nama_replacement'   => $rmp->nama_karyawan_replacement,
            ];

            Notification::make()
                ->title(__('rekrutmen::app.public_request_form.notifications.success.title'))
                ->body(__('rekrutmen::app.public_request_form.notifications.success.body'))
                ->success()
                ->send();
            $this->dispatch('form-processing-finished');

            return redirect()->to($rmp->getPublicProgressUrl());
        } catch (ValidationException $e) {
            $this->dispatch('form-processing-finished');
            throw $e;
        } catch (QueryException $e) {
            Log::error('Public request man power submission failed (query exception).', [
                'exception' => $e,
            ]);

            $this->addError('data', __('rekrutmen::app.public_request_form.errors.system'));
            $this->dispatch('form-processing-finished');

            return null;
        } catch (Throwable $e) {
            Log::error('Public request man power submission failed.', [
                'exception' => $e,
            ]);

            $this->addError('data', __('rekrutmen::app.public_request_form.errors.system'));
            $this->dispatch('form-processing-finished');

            return null;
        }

        return null;
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
            ->label(__('rekrutmen::app.public_request_form.actions.submit'))
            ->extraAttributes([
                'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
            ], merge: true)
            ->submit('submit');
    }

    protected function isReplacementStatus(mixed $statusKebutuhan): bool
    {
        if ($statusKebutuhan instanceof StatusKebutuhan) {
            return $statusKebutuhan === StatusKebutuhan::REPLACEMENT;
        }

        if (! is_string($statusKebutuhan)) {
            return false;
        }

        return in_array($statusKebutuhan, [StatusKebutuhan::REPLACEMENT->value, StatusKebutuhan::REPLACEMENT->name], true);
    }

    protected function handleValidationError(): void
    {
        Notification::make()
            ->title(__('rekrutmen::app.public_request_form.notifications.validation.title'))
            ->body(__('rekrutmen::app.public_request_form.notifications.validation.body'))
            ->warning()
            ->send();
    }

    public function isRecaptchaEnabled(): bool
    {
        return $this->recaptchaEnabled;
    }

    public function getRecaptchaSiteKey(): ?string
    {
        return $this->recaptchaSiteKey;
    }

    public function getRecaptchaAction(): string
    {
        return $this->recaptchaAction ?? 'request_man_power';
    }

    protected function verifyRecaptchaToken(array $state): bool
    {
        $token = Arr::get($state, 'recaptcha_token');

        if (! $token) {
            $this->addError('data.recaptcha_token', __('rekrutmen::app.public_request_form.errors.recaptcha_required'));

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout($this->recaptchaTimeout)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => $this->recaptchaSecretKey,
                    'response' => $token,
                    'remoteip' => request()?->ip(),
                ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA verification request failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                $this->addError('data.recaptcha_token', __('rekrutmen::app.public_request_form.errors.recaptcha_failed'));

                return false;
            }

            $payload = $response->json();

            if (! Arr::get($payload, 'success')) {
                Log::info('reCAPTCHA verification rejected.', [
                    'errors' => Arr::get($payload, 'error-codes'),
                ]);

                $this->addError('data.recaptcha_token', __('rekrutmen::app.public_request_form.errors.recaptcha_failed'));

                return false;
            }

            $score = (float) Arr::get($payload, 'score', 1.0);

            if (
                $this->recaptchaScoreThreshold > 0
                && Arr::has($payload, 'score')
                && $score < $this->recaptchaScoreThreshold
            ) {
                Log::info('reCAPTCHA score below configured threshold.', [
                    'score'     => $score,
                    'threshold' => $this->recaptchaScoreThreshold,
                ]);

                $this->addError('data.recaptcha_token', __('rekrutmen::app.public_request_form.errors.recaptcha_failed'));

                return false;
            }

            $action = Arr::get($payload, 'action');

            if ($action && $this->recaptchaAction && $action !== $this->recaptchaAction) {
                Log::info('reCAPTCHA action mismatch detected.', [
                    'expected' => $this->recaptchaAction,
                    'received' => $action,
                ]);

                $this->addError('data.recaptcha_token', __('rekrutmen::app.public_request_form.errors.recaptcha_failed'));

                return false;
            }
        } catch (Throwable $exception) {
            Log::warning('reCAPTCHA verification failed with exception.', [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('data.recaptcha_token', __('rekrutmen::app.public_request_form.errors.recaptcha_failed'));

            return false;
        }

        return true;
    }
}
