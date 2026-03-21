<?php

namespace Cesa\Presensi\Filament\Resources\OvertimeResource\Pages;

use Cesa\Presensi\Filament\Resources\OvertimeResource;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Overtime;
use Cesa\Presensi\Models\Schedule;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EditOvertime extends EditRecord
{
    protected static string $resource = OvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        $status = $data['status'] ?? $this->record->status;

        if ($status !== 'approved') {
            return;
        }

        $dateValue = $data['date'] ?? $this->record->date;
        $requestDate = Carbon::parse($dateValue)->toDateString();
        $requestDateAt = Carbon::parse($dateValue)->startOfDay();
        $todayAt = Carbon::today();
        $userId = $this->record->user_id;

        if ($requestDateAt->greaterThan($todayAt)) {
            throw ValidationException::withMessages([
                'status' => 'Belum bisa approve sebelum tanggal lembur.',
            ]);
        }

        $onLeave = Leave::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $requestDate)
            ->whereDate('end_date', '>=', $requestDate)
            ->exists();

        if ($onLeave) {
            throw ValidationException::withMessages([
                'status' => 'User sedang cuti pada tanggal tersebut.',
            ]);
        }

        $requiresAttendance = $requestDateAt->lessThan($todayAt);
        $schedule = Schedule::resolveActiveForUser($userId, $requestDateAt);
        $attendance = null;

        if ($requiresAttendance) {
            $attendance = Attendance::query()
                ->where('user_id', $userId)
                ->forAttendanceDate($requestDate)
                ->orderByAttendanceDate()
                ->first();

            if (! $attendance) {
                throw ValidationException::withMessages([
                    'status' => 'Attendance belum ada untuk tanggal tersebut.',
                ]);
            }
        }

        if (! $attendance && (! $schedule || ! $schedule->shift)) {
            throw ValidationException::withMessages([
                'status' => 'User belum mendapatkan jadwal kerja.',
            ]);
        }
        $startTime = $this->normalizeTime($data['start_time'] ?? $this->record->start_time);
        $endTime = $this->normalizeTime($data['end_time'] ?? $this->record->end_time);

        if (! $startTime || ! $endTime) {
            throw ValidationException::withMessages([
                'status' => 'Waktu lembur tidak valid.',
            ]);
        }

        $startAt = Carbon::createFromFormat('H:i:s', $startTime);
        $endAt = Carbon::createFromFormat('H:i:s', $endTime);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw ValidationException::withMessages([
                'status' => 'Waktu lembur harus berakhir setelah waktu mulai.',
            ]);
        }

        $effectiveScheduleStart = $attendance?->schedule_start_time ?? $schedule?->shift?->start_time;
        $effectiveScheduleEnd = $attendance?->schedule_end_time ?? $schedule?->shift?->end_time;

        $scheduleStartTime = $this->normalizeTime($effectiveScheduleStart);
        $scheduleEndTime = $this->normalizeTime($effectiveScheduleEnd);

        if (! $scheduleStartTime || ! $scheduleEndTime) {
            throw ValidationException::withMessages([
                'status' => 'Waktu shift tidak valid.',
            ]);
        }

        $scheduleStartAt = Carbon::createFromFormat('H:i:s', $scheduleStartTime);
        $scheduleEndAt = Carbon::createFromFormat('H:i:s', $scheduleEndTime);

        $outsideShift = $endAt->lessThanOrEqualTo($scheduleStartAt)
            || $startAt->greaterThanOrEqualTo($scheduleEndAt);
        if (! $outsideShift) {
            throw ValidationException::withMessages([
                'status' => 'Waktu lembur harus di luar jam kerja.',
            ]);
        }

        $earlyOvertime = $endAt->lessThanOrEqualTo($scheduleStartAt);
        $afterOvertime = $startAt->greaterThanOrEqualTo($scheduleEndAt);

        if ($requiresAttendance) {
            if ($earlyOvertime) {
                $attendanceStartTime = $this->normalizeTime($attendance->start_time);
                if (! $attendanceStartTime) {
                    throw ValidationException::withMessages([
                        'status' => 'Waktu masuk presensi tidak valid.',
                    ]);
                }

                $attendanceStartAt = Carbon::createFromFormat('H:i:s', $attendanceStartTime);
                if ($attendanceStartAt->greaterThan($startAt)) {
                    throw ValidationException::withMessages([
                        'status' => 'Waktu mulai lembur harus sebelum atau sama dengan waktu masuk presensi.',
                    ]);
                }
            }

            if ($afterOvertime) {
                $attendanceEndTime = $this->normalizeTime($attendance->end_time);
                if (! $attendanceEndTime) {
                    throw ValidationException::withMessages([
                        'status' => 'Waktu pulang presensi tidak valid.',
                    ]);
                }

                $attendanceEndAt = Carbon::createFromFormat('H:i:s', $attendanceEndTime);
                if ($attendanceEndAt->lessThan($endAt)) {
                    throw ValidationException::withMessages([
                        'status' => 'Waktu lembur melebihi waktu pulang pada presensi.',
                    ]);
                }
            }
        }

        $overlap = Overtime::where('user_id', $userId)
            ->whereDate('date', $requestDate)
            ->where('id', '!=', $this->record->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan lembur tumpang tindih dengan data lain.',
            ]);
        }
    }

    private function normalizeTime($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        if ($value === null) {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $time = Carbon::createFromFormat($format, $value);

                return $time->format('H:i:s');
            } catch (\Exception $e) {
                // Try next format
            }
        }

        return null;
    }
}
