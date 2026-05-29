<?php

namespace Cesa\Padelnis\Livewire;

use Cesa\Padelnis\Models\Reservation;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;
use Webkul\PluginManager\Package;

class PublicReservationForm extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'padelnis::layouts.form';

    protected string $view = 'padelnis::livewire.public-reservation-form';

    public ?array $data = [];

    public function mount(): void
    {
        if (! Package::isPluginInstalled('padelnis')) {
            abort(404);
        }

        $this->data = array_replace($this->defaultFormData(), $this->data ?? []);

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('catalog_item_id')
                    ->label(__('padelnis::filament/resources/reservation.fields.catalog_item'))
                    ->options(fn (): array => Reservation::publicCatalogItemOptions())
                    ->searchable()
                    ->live()
                    ->required()
                    ->rule(fn () => Rule::in(array_keys(Reservation::publicCatalogItemOptions())))
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        $set('court', null);
                        $set('coach_id', null);
                        $set('reservation_time', null);
                        $set('expected_amount', null);
                        $set('transfer_amount', null);

                        static::refreshExpectedAmount($set, $get);
                    })
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.catalog_item')),
                TextInput::make('customer_name')
                    ->label(__('padelnis::filament/resources/reservation.fields.customer_name'))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.customer_name')),
                DatePicker::make('reservation_date')
                    ->label(__('padelnis::filament/resources/reservation.fields.reservation_date'))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                    ->required()
                    ->displayFormat('Y-m-d')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        static::refreshExpectedAmount($set, $get);
                    })
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.reservation_date')),
                Select::make('court')
                    ->label(__('padelnis::filament/resources/reservation.fields.court'))
                    ->options(fn (): array => Reservation::courtOptions())
                    ->searchable()
                    ->required(fn (Get $get): bool => Reservation::catalogItemRequiresCourt($get('catalog_item_id')))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')) && Reservation::catalogItemRequiresCourt($get('catalog_item_id')))
                    ->live()
                    ->rule(fn (Get $get): mixed => Reservation::catalogItemRequiresCourt($get('catalog_item_id'))
                        ? Rule::in(array_keys(Reservation::courtOptions()))
                        : null)
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        static::refreshExpectedAmount($set, $get);
                    })
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.court')),
                Select::make('coach_id')
                    ->label(__('padelnis::filament/resources/reservation.fields.coach'))
                    ->options(fn (): array => Reservation::coachOptions())
                    ->searchable()
                    ->live()
                    ->required(fn (Get $get): bool => Reservation::catalogItemRequiresCoach($get('catalog_item_id')))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')) && Reservation::catalogItemRequiresCoach($get('catalog_item_id')))
                    ->rule(fn (Get $get): mixed => Reservation::catalogItemRequiresCoach($get('catalog_item_id'))
                        ? Rule::in(array_keys(Reservation::coachOptions()))
                        : null)
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        static::refreshExpectedAmount($set, $get);
                    })
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.coach')),
                Select::make('reservation_time')
                    ->label(__('padelnis::filament/resources/reservation.fields.reservation_time'))
                    ->options(fn (): array => Reservation::reservableTimeOptions())
                    ->searchable()
                    ->required(fn (Get $get): bool => Reservation::catalogItemUsesTimedResources($get('catalog_item_id')))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')) && Reservation::catalogItemUsesTimedResources($get('catalog_item_id')))
                    ->live()
                    ->rule(fn (Get $get): mixed => Reservation::catalogItemUsesTimedResources($get('catalog_item_id'))
                        ? Rule::in(array_keys(Reservation::reservableTimeOptions()))
                        : null)
                    ->rule(static fn (Get $get): Closure => static::activeSlotValidationRule($get))
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        static::refreshExpectedAmount($set, $get);
                    })
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.reservation_time')),
                TextInput::make('expected_amount')
                    ->label(__('padelnis::filament/resources/reservation.fields.expected_amount'))
                    ->inputMode('numeric')
                    ->prefix('Rp')
                    ->readOnly()
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                    ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                    ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.expected_amount')),
                TextInput::make('transfer_amount')
                    ->label(__('padelnis::filament/resources/reservation.fields.transfer_amount'))
                    ->inputMode('numeric')
                    ->prefix('Rp')
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                    ->required()
                    ->rule('numeric')
                    ->rule('min:0')
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.transfer_amount'))
                    ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                    ->mutateStateForValidationUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->extraAlpineAttributes([
                        'x-on:input' => static::transferAmountMaskAlpineExpression(),
                        'x-on:blur'  => static::transferAmountMaskAlpineExpression(),
                        'x-init'     => static::transferAmountMaskAlpineExpression(),
                    ]),
                DatePicker::make('transfer_date')
                    ->label(__('padelnis::filament/resources/reservation.fields.transfer_date'))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                    ->displayFormat('Y-m-d')
                    ->native(false)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.transfer_date')),
                Textarea::make('notes')
                    ->label(__('padelnis::filament/resources/reservation.fields.notes'))
                    ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                    ->nullable()
                    ->maxLength(1000)
                    ->rows(3)
                    ->placeholder(__('padelnis::views/public-reservation-form.placeholders.notes')),
            ])
            ->statePath('data');
    }

    public function submit(): mixed
    {
        $state = $this->form->getState();

        try {
            $reservation = Reservation::query()->create([

                'catalog_item_id'  => Arr::get($state, 'catalog_item_id'),
                'customer_name'    => Arr::get($state, 'customer_name'),
                'reservation_date' => Arr::get($state, 'reservation_date'),
                'court'            => Arr::get($state, 'court'),
                'coach_id'         => Arr::get($state, 'coach_id'),
                'reservation_time' => Arr::get($state, 'reservation_time'),
                'transfer_amount'  => Arr::get($state, 'transfer_amount'),
                'transfer_date'    => Arr::get($state, 'transfer_date'),
                'notes'            => Arr::get($state, 'notes'),
            ]);

            $this->resetFormAfterSubmission();

            return redirect()->to(URL::signedRoute('padelnis.public.success', [
                'idReff' => $reservation->id_reff,
            ]));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Failed to submit Padelnis reservation.', [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('data', __('padelnis::views/public-reservation-form.messages.generic'));

            return null;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('padelnis::views/public-reservation-form.actions.submit'))
                ->extraAttributes([
                    'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
                ], merge: true)
                ->submit('submit'),
        ];
    }

    protected static function transferAmountMaskAlpineExpression(): string
    {
        return <<<'JS'
const value = String($el.value);
const integer = value.replace(/,\d{0,2}$/, '').replace(/\D/g, '');
$el.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
JS;
    }

    protected function resetFormAfterSubmission(): void
    {
        $this->data = $this->defaultFormData();
        $this->form->fill($this->data);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultFormData(): array
    {
        return [];
    }

    protected static function refreshExpectedAmount(Set $set, Get $get): void
    {
        $currentExpectedAmount = Reservation::normalizeTransferAmount($get('expected_amount'));
        $currentTransferAmount = Reservation::normalizeTransferAmount($get('transfer_amount'));
        $expectedAmount = Reservation::resolveExpectedAmount(
            $get('catalog_item_id'),
            $get('reservation_date'),
            $get('court'),
            $get('coach_id'),
            $get('reservation_time'),
        );

        $formattedExpectedAmount = Reservation::formatTransferAmountForForm($expectedAmount);

        $set('expected_amount', $formattedExpectedAmount);

        if (filled($formattedExpectedAmount) && (blank($currentTransferAmount) || $currentTransferAmount === $currentExpectedAmount)) {
            $set('transfer_amount', $formattedExpectedAmount);
        }
    }

    protected static function activeSlotValidationRule(Get $get): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
            if (Reservation::activeSlotExists(
                $get('court'),
                $get('reservation_date'),
                $value,
                null,
                $get('catalog_item_id'),
                $get('coach_id'),
            )) {
                $fail(__('padelnis::filament/resources/reservation.validation.active_slot_unique'));
            }
        };
    }
}
