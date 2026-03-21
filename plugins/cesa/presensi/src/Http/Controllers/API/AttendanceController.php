<?php

namespace Cesa\Presensi\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Cesa\Presensi\Http\Requests\CheckInRequest;
use Cesa\Presensi\Http\Requests\CheckOutRequest;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Webkul\Security\Models\User as SecurityUser;

class AttendanceController extends Controller
{
    public function getAttendanceToday(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $today = now()->startOfDay();

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->forAttendanceDate($today)
            ->latest('created_at')
            ->first();

        $attendanceThisMonth = Attendance::query()
            ->where('user_id', $user->id)
            ->forAttendanceMonth($today->month, $today->year)
            ->orderByAttendanceDate()
            ->get()
            ->map(fn (Attendance $attendance): array => $this->attendanceSummaryPayload($attendance))
            ->values()
            ->all();

        $activeSchedule = Schedule::resolveActiveForUser($user->id);
        $isOnLeave = $this->isUserOnLeave($user->id, $today);

        return response()->json([
            'success' => true,
            'message' => 'Attendance retrieved successfully.',
            'data'    => [
                'today'           => $todayAttendance ? $this->attendancePayload($todayAttendance) : null,
                'today_state'     => $todayAttendance?->resolvedAttendanceStatus() ?? ($isOnLeave ? Attendance::STATUS_ON_LEAVE : 'not_checked_in'),
                'active_schedule' => $activeSchedule ? $this->schedulePayload($activeSchedule) : null,
                'this_month'      => $attendanceThisMonth,
            ],
        ]);
    }

    public function getSchedule(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $schedule = Schedule::resolveActiveForUser($user->id);

        $validationResult = $this->validateSchedule($schedule);
        if ($validationResult !== null) {
            return $validationResult;
        }

        return response()->json([
            'success' => true,
            'message' => 'Success get schedule',
            'data'    => $this->schedulePayload($schedule),
        ]);
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $today = now()->startOfDay();
        $schedule = Schedule::resolveActiveForUser($user->id);

        $validationResult = $this->validateSchedule($schedule);
        if ($validationResult !== null) {
            return $validationResult;
        }

        if ($this->isUserOnLeave($user->id, $today)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat melakukan presensi karena sedang cuti.',
                'data'    => null,
            ], 422);
        }

        $existingAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->forAttendanceDate($today)
            ->latest('created_at')
            ->first();

        if ($existingAttendance instanceof Attendance) {
            return response()->json([
                'success' => false,
                'message' => $existingAttendance->end_time
                    ? 'Presensi hari ini sudah selesai diproses.'
                    : 'Anda sudah melakukan check in hari ini.',
            ], 409);
        }

        $mockLocationResult = $this->validateMockLocation($request);
        if ($mockLocationResult !== null) {
            return $mockLocationResult;
        }

        $locationResult = $this->validateScheduleRadius(
            $schedule,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
        );
        if ($locationResult !== null) {
            return $locationResult;
        }

        $photoPath = $request->file('photo')->store('presensi/photos', 'public');

        $attendance = Attendance::query()->create([
            'user_id'             => $user->id,
            'schedule_latitude'   => $schedule->office->latitude,
            'schedule_longitude'  => $schedule->office->longitude,
            'schedule_start_time' => $schedule->shift->start_time,
            'schedule_end_time'   => $schedule->shift->end_time,
            'start_latitude'      => $request->input('latitude'),
            'start_longitude'     => $request->input('longitude'),
            'start_time'          => Carbon::now()->toTimeString(),
            'start_photo_path'    => $photoPath,
            'end_time'            => null,
            'is_leave'            => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check in recorded successfully.',
            'data'    => $this->attendancePayload($attendance->fresh()),
        ], 201);
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->latest('created_at')
            ->first();

        if (! $attendance instanceof Attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data check in aktif yang bisa di-check out.',
            ], 422);
        }

        if ($attendance->attendanceDate()?->lt(now()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'Presensi terbuka dari hari sebelumnya harus diselesaikan secara manual oleh admin.',
            ], 422);
        }

        $schedule = Schedule::resolveActiveForUser($user->id);

        $validationResult = $this->validateSchedule($schedule);
        if ($validationResult !== null) {
            return $validationResult;
        }

        $mockLocationResult = $this->validateMockLocation($request);
        if ($mockLocationResult !== null) {
            return $mockLocationResult;
        }

        $locationResult = $this->validateScheduleRadius(
            $schedule,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
        );
        if ($locationResult !== null) {
            return $locationResult;
        }

        $checkedInAt = $attendance->checkedInAt();
        $checkedOutAt = Carbon::now();

        if (! $checkedInAt instanceof Carbon || $checkedOutAt->lessThanOrEqualTo($checkedInAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Waktu check out tidak valid.',
            ], 422);
        }

        $photoPath = $request->file('photo')->store('presensi/photos', 'public');

        $attendance->update([
            'end_latitude'   => $request->input('latitude'),
            'end_longitude'  => $request->input('longitude'),
            'end_time'       => $checkedOutAt->toTimeString(),
            'end_photo_path' => $photoPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check out recorded successfully.',
            'data'    => $this->attendancePayload($attendance->fresh()),
        ]);
    }

    public function getAttendanceByMonthAndYear(Request $request, int $month, int $year): JsonResponse
    {
        $validator = Validator::make(['month' => $month, 'year' => $year], [
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:1900|max:'.date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $this->resolveUser($request);

        $attendanceList = Attendance::query()
            ->where('user_id', $user->id)
            ->forAttendanceMonth($month, $year)
            ->orderByAttendanceDate()
            ->get()
            ->map(fn (Attendance $attendance): array => $this->attendanceSummaryPayload($attendance))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Attendance retrieved successfully.',
            'data'    => $attendanceList,
        ]);
    }

    public function banned(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $schedule = Schedule::resolveActiveForUser($user->id);

        if (! $schedule instanceof Schedule) {
            return response()->json([
                'success' => false,
                'message' => 'User belum mendapatkan jadwal kerja, segera hubungi Admin.',
                'data'    => null,
            ], 422);
        }

        abort_unless($user instanceof SecurityUser, 403, 'Authenticated user is invalid.');

        Gate::forUser($user)->authorize('update', $schedule);

        $schedule->update([
            'is_banned' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Success banned schedule',
            'data'    => $this->schedulePayload($schedule->fresh(['office', 'shift'])),
        ]);
    }

    public function getPhoto(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        return response()->json([
            'success' => true,
            'message' => 'Success get photo profile',
            'data'    => $user->avatar_url,
        ]);
    }

    private function resolveUser(Request $request)
    {
        return $request->user();
    }

    private function validateSchedule(?Schedule $schedule): ?JsonResponse
    {
        if (! $schedule instanceof Schedule) {
            return response()->json([
                'success' => false,
                'message' => 'User belum mendapatkan jadwal kerja, segera hubungi Admin.',
                'data'    => null,
            ], 422);
        }

        if ($schedule->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda diblokir dari presensi. Hubungi Admin.',
                'data'    => null,
            ], 403);
        }

        if (! $schedule->shift || ! $schedule->office) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal kerja belum lengkap. Hubungi Admin.',
                'data'    => null,
            ], 422);
        }

        return null;
    }

    private function validateMockLocation(Request $request): ?JsonResponse
    {
        if ($request->boolean('is_mock_location') && config('presensi.reject_mock_location', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Terdeteksi penggunaan lokasi palsu (mock location). Nonaktifkan mock location untuk melanjutkan.',
            ], 422);
        }

        return null;
    }

    private function validateScheduleRadius(Schedule $schedule, float $latitude, float $longitude): ?JsonResponse
    {
        if ($schedule->is_wfa) {
            return null;
        }

        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            (float) $schedule->office->latitude,
            (float) $schedule->office->longitude,
        );

        if ($distance > $schedule->office->radius) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius kantor ('.round($distance).'m). Max: '.$schedule->office->radius.'m',
            ], 422);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePayload(Attendance $attendance): array
    {
        return [
            'id'                => $attendance->id,
            'date'              => $attendance->attendanceDate()?->toDateString(),
            'start_time'        => $attendance->start_time,
            'end_time'          => $attendance->end_time,
            'check_in_status'   => $attendance->resolvedCheckInStatus(),
            'check_out_status'  => $attendance->resolvedCheckOutStatus(),
            'attendance_status' => $attendance->resolvedAttendanceStatus(),
            'attendance_flags'  => $attendance->resolvedAttendanceFlags(),
            'is_late'           => $attendance->isLate(),
            'is_early_leave'    => $attendance->isEarlyLeave(),
            'work_duration'     => $attendance->workDuration(),
            'schedule'          => [
                'start_time' => $attendance->schedule_start_time,
                'end_time'   => $attendance->schedule_end_time,
                'latitude'   => $attendance->schedule_latitude,
                'longitude'  => $attendance->schedule_longitude,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceSummaryPayload(Attendance $attendance): array
    {
        return [
            'id'                => $attendance->id,
            'date'              => $attendance->attendanceDate()?->toDateString(),
            'start_time'        => $attendance->start_time,
            'end_time'          => $attendance->end_time,
            'check_in_status'   => $attendance->resolvedCheckInStatus(),
            'check_out_status'  => $attendance->resolvedCheckOutStatus(),
            'attendance_status' => $attendance->resolvedAttendanceStatus(),
            'attendance_flags'  => $attendance->resolvedAttendanceFlags(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulePayload(Schedule $schedule): array
    {
        return [
            'id'            => $schedule->id,
            'user_id'       => $schedule->user_id,
            'office_id'     => $schedule->office_id,
            'office_name'   => $schedule->office?->name,
            'office_radius' => $schedule->office?->radius,
            'shift_id'      => $schedule->shift_id,
            'shift_name'    => $schedule->shift?->name,
            'start_time'    => $schedule->shift?->start_time,
            'end_time'      => $schedule->shift?->end_time,
            'is_wfa'        => $schedule->is_wfa,
            'is_banned'     => $schedule->is_banned,
        ];
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($lat1) * cos($lat2) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function isUserOnLeave(int $userId, Carbon $date): bool
    {
        $dateString = $date->toDateString();

        return Leave::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();
    }
}
