<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Security\Models\User;

class RequestManPower extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rekrutmen_request_man_powers';

    const LEVEL_PEKERJAAN_OPTIONS = ['Staff', 'Leader', 'Coordinator', 'Manager'];

    const STATUS_KEBUTUHAN_OPTIONS = ['New Hiring', 'Replacement'];

    protected $fillable = [
        'email_address',
        'nama_pengaju',
        'posisi_pengaju',
        'tanggal_pengajuan',
        'posisi_dibutuhkan',
        'lokasi_penempatan',
        'status_kebutuhan',
        'divisi',
        'level_pekerjaan',
        'nama_karyawan_replacement',
        'badan_usaha',
        'jumlah_karyawan_dibutuhkan',
        'estimasi_tanggal_join',
        'requirements_kualifikasi',
        'job_description',
        'status_response_id',
        'keterangan',
        'status',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'status_kebutuhan'           => StatusKebutuhan::class,
            'status'                     => RequestManPowerStatus::class,
            'tanggal_pengajuan'          => 'date',
            'estimasi_tanggal_join'      => 'date',
            'jumlah_karyawan_dibutuhkan' => 'integer',
            'status_response_id'         => 'string',
            'created_at'                 => 'datetime',
            'updated_at'                 => 'datetime',
            'deleted_at'                 => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (blank($request->status_response_id)) {
                $request->status_response_id = (string) Str::uuid();
            }
        });
    }

    protected function namaKaryawanReplacement(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes): ?string {
                $statusKebutuhan = $attributes['status_kebutuhan'] ?? $this->status_kebutuhan;
                $statusKebutuhanValue = $statusKebutuhan instanceof StatusKebutuhan
                    ? $statusKebutuhan->value
                    : $statusKebutuhan;

                if ($statusKebutuhanValue !== StatusKebutuhan::REPLACEMENT->value) {
                    return null;
                }

                if (blank($value)) {
                    return null;
                }

                return trim($value);
            },
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function jobPosting(): HasOne
    {
        return $this->hasOne(JobPosting::class, 'request_man_power_id')->withTrashed();
    }

    public function isReplacement(): bool
    {
        return $this->status_kebutuhan === StatusKebutuhan::REPLACEMENT;
    }

    public function getTanggalPengajuanFormattedAttribute(): string
    {
        return $this->tanggal_pengajuan?->translatedFormat('d F Y') ?? '-';
    }

    public function getEstimasiTanggalJoinFormattedAttribute(): string
    {
        return $this->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-';
    }

    public function getPublicProgressUrl(): string
    {
        if (blank($this->status_response_id)) {
            return url('man-power');
        }

        return url('man-power/progress/'.$this->status_response_id);
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslatedLevelPekerjaanOptions(): array
    {
        return collect(self::LEVEL_PEKERJAAN_OPTIONS)
            ->mapWithKeys(fn (string $option) => [
                $option => __('rekrutmen::app.enums.level_pekerjaan.'.Str::snake($option)),
            ])
            ->all();
    }

    public function scopeByDivisi(Builder $query, string $divisi): Builder
    {
        return $query->where('divisi', $divisi);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByTanggal(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('tanggal_pengajuan', [$from, $to]);
    }

    public function sendSubmittedNotification(): void
    {
        if (blank($this->email_address)) {
            return;
        }

        try {
            NotificationFacade::route('mail', $this->email_address)
                ->notify(new RequestManPowerSubmittedNotification($this));
        } catch (Throwable $e) {
            Log::error('Failed to send request man power submitted notification.', [
                'request_man_power_id' => $this->getKey(),
                'email'                => $this->email_address,
                'exception'            => $e,
            ]);
        }
    }

    public function sendStatusChangedNotification(mixed $fromStatus, mixed $toStatus): void
    {
        if (blank($this->email_address)) {
            return;
        }

        $from = $this->normalizeStatus($fromStatus);
        $to = $this->normalizeStatus($toStatus);

        if (! $to) {
            return;
        }

        if ($from?->value === $to->value) {
            return;
        }

        try {
            NotificationFacade::route('mail', $this->email_address)
                ->notify(new RequestManPowerStatusChangedNotification($this, $from, $to));
        } catch (Throwable $e) {
            Log::error('Failed to send request man power status changed notification.', [
                'request_man_power_id' => $this->getKey(),
                'email'                => $this->email_address,
                'from_status'          => $from?->value,
                'to_status'            => $to->value,
                'exception'            => $e,
            ]);
        }
    }

    public function approveBy(?int $approverId = null): void
    {
        $previousStatus = $this->status;

        $this->update([
            'status'      => RequestManPowerStatus::APPROVED,
            'approved_by' => $approverId,
        ]);

        $this->createJobPostingIfMissing();

        $this->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::APPROVED);
    }

    public function createJobPostingIfMissing(): JobPosting
    {
        $title = trim(implode(' ', array_filter([
            $this->posisi_dibutuhkan,
            $this->lokasi_penempatan,
        ])));

        if ($title === '') {
            $title = __('rekrutmen::app.resources.job_posting.generated_title', ['id' => $this->getKey()]);
        }

        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'job-posting-'.$this->getKey();
        $slug = $baseSlug;
        $suffix = 2;

        while (
            JobPosting::query()
                ->where('slug', $slug)
                ->where('request_man_power_id', '!=', $this->getKey())
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return JobPosting::query()->firstOrCreate(
            ['request_man_power_id' => $this->getKey()],
            [
                'rekrutmen_pipeline_id' => RekrutmenPipeline::query()->value('id'),
                'title'                 => $title,
                'slug'                  => $slug,
                'description'           => $this->job_description,
                'requirements'          => $this->requirements_kualifikasi,
                'location'              => $this->lokasi_penempatan,
                'is_published'          => false,
                'closing_date'          => $this->estimasi_tanggal_join,
            ],
        );
    }

    private function normalizeStatus(mixed $status): ?RequestManPowerStatus
    {
        if ($status instanceof RequestManPowerStatus) {
            return $status;
        }

        if (! is_string($status) || blank($status)) {
            return null;
        }

        return RequestManPowerStatus::tryFrom($status);
    }
}

class RequestManPowerSubmittedNotification extends Notification
{
    public function __construct(private readonly RequestManPower $requestManPower) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('rekrutmen::app.mail.request_man_power_submitted.subject'))
            ->greeting(__('rekrutmen::app.mail.request_man_power_submitted.greeting', ['name' => $this->requestManPower->nama_pengaju]))
            ->line(__('rekrutmen::app.mail.request_man_power_submitted.body'))
            ->line(__('rekrutmen::app.mail.request_man_power_submitted.position', ['value' => $this->requestManPower->posisi_dibutuhkan]))
            ->line(__('rekrutmen::app.mail.request_man_power_submitted.requirement_status', ['value' => $this->requestManPower->status_kebutuhan->getLabel()]))
            ->line(__('rekrutmen::app.mail.request_man_power_submitted.submission_id', ['id' => $this->requestManPower->status_response_id]))
            ->action(
                __('rekrutmen::app.mail.request_man_power_submitted.view_progress'),
                $this->requestManPower->getPublicProgressUrl(),
            );
    }
}

class RequestManPowerStatusChangedNotification extends Notification
{
    public function __construct(
        private readonly RequestManPower $requestManPower,
        private readonly ?RequestManPowerStatus $fromStatus,
        private readonly RequestManPowerStatus $toStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('rekrutmen::app.mail.request_man_power_status_changed.subject'))
            ->greeting(__('rekrutmen::app.mail.request_man_power_status_changed.greeting', ['name' => $this->requestManPower->nama_pengaju]))
            ->line(__('rekrutmen::app.mail.request_man_power_status_changed.body'))
            ->line(__('rekrutmen::app.mail.request_man_power_status_changed.position', ['value' => $this->requestManPower->posisi_dibutuhkan]))
            ->line(__('rekrutmen::app.mail.request_man_power_status_changed.latest_status', ['value' => $this->toStatus->getLabel()]));

        if ($this->fromStatus) {
            $mail->line(__('rekrutmen::app.mail.request_man_power_status_changed.previous_status', ['value' => $this->fromStatus->getLabel()]));
        }

        return $mail
            ->line(__('rekrutmen::app.mail.request_man_power_status_changed.submission_id', ['id' => $this->requestManPower->status_response_id]))
            ->action(
                __('rekrutmen::app.mail.request_man_power_status_changed.view_progress'),
                $this->requestManPower->getPublicProgressUrl(),
            );
    }
}
