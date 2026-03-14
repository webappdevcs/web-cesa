<?php

namespace Cesa\Presensi\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function getAttendanceToday(): JsonResponse
    {
        $userId = auth()->user()->id;
        $today = now()->toDateString();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $attendanceToday = Attendance::select('start_time', 'end_time')
            ->where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->first();

        $attendanceThisMonth = Attendance::select('start_time', 'end_time', 'created_at')
            ->where('user_id', $userId)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attendance) {
                return [
                    'start_time' => $attendance->start_time,
                    'end_time'   => $attendance->end_time,
                    'date'       => $attendance->created_at->toDateString(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Attendance retrieved successfully.',
            'data'    => [
                'today'      => $attendanceToday,
                'this_month' => $attendanceThisMonth,
            ],
        ]);
    }

    public function getSchedule(): JsonResponse
    {
        $schedule = Schedule::with(['office', 'shift'])->where('user_id', auth()->user()->id)->first();

        $validationResult = $this->validateSchedule($schedule);
        if ($validationResult !== null) {
            return $validationResult;
        }

        return response()->json([
            'success' => true,
            'message' => 'Success get schedule',
            'data'    => $schedule,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude'         => 'required|numeric|between:-90,90',
            'longitude'        => 'required|numeric|between:-180,180',
            'photo'            => 'required|image|max:10240',
            'is_mock_location' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_mock_location') && config('presensi.reject_mock_location', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Terdeteksi penggunaan lokasi palsu (mock location). Nonaktifkan mock location untuk melanjutkan.',
            ], 422);
        }

        $user = Auth::user();
        $schedule = Schedule::with(['office', 'shift'])->where('user_id', $user->id)->first();

        $validationResult = $this->validateSchedule($schedule);
        if ($validationResult !== null) {
            return $validationResult;
        }

        if (! $schedule->is_wfa) {
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $schedule->office->latitude,
                $schedule->office->longitude
            );

            if ($distance > $schedule->office->radius) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda berada di luar radius kantor ('.round($distance).'m). Max: '.$schedule->office->radius.'m',
                ], 422);
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('presensi/photos', 'public');
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())->first();

        if (! $attendance) {
            $attendance = Attendance::create([
                'user_id'             => $user->id,
                'schedule_latitude'   => $schedule->office->latitude,
                'schedule_longitude'  => $schedule->office->longitude,
                'schedule_start_time' => $schedule->shift->start_time,
                'schedule_end_time'   => $schedule->shift->end_time,
                'start_latitude'      => $request->latitude,
                'start_longitude'     => $request->longitude,
                'start_time'          => Carbon::now()->toTimeString(),
                'end_time'            => null,
                'start_photo_path'    => $photoPath,
            ]);
        } else {
            $dataToUpdate = [
                'end_latitude'  => $request->latitude,
                'end_longitude' => $request->longitude,
                'end_time'      => Carbon::now()->toTimeString(),
            ];

            if ($photoPath) {
                $dataToUpdate['end_photo_path'] = $photoPath;
            }

            $attendance->update($dataToUpdate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully.',
            'data'    => $attendance,
        ]);
    }

    public function getAttendanceByMonthAndYear($month, $year): JsonResponse
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

        $userId = auth()->user()->id;
        $attendanceList = Attendance::where('user_id', $userId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($attendance) {
                return [
                    'start_time' => $attendance->start_time,
                    'end_time'   => $attendance->end_time,
                    'date'       => $attendance->created_at->toDateString(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Attendance retrieved successfully.',
            'data'    => $attendanceList,
        ]);
    }

    public function banned()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        if ($schedule) {
            $schedule->update([
                'is_banned' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Success banned schedule',
            'data'    => $schedule,
        ]);
    }

    public function getPhoto()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'message' => 'Success get photo profile',
            'data'    => $user->avatar_url,
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     * Returns distance in meters.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

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

    /**
     * Check if the user is currently on approved leave.
     */
    private function isUserOnLeave(int $userId): bool
    {
        $today = Carbon::today()->format('Y-m-d');

        return Leave::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();
    }

    /**
     * Validate schedule and return error response if invalid.
     *
     * @return \Illuminate\Http\JsonResponse|null Returns null if schedule is valid
     */
    private function validateSchedule(?Schedule $schedule, bool $checkLeaveStatus = true): ?\Illuminate\Http\JsonResponse
    {
        if ($schedule === null) {
            return response()->json([
                'success' => false,
                'message' => 'User belum mendapatkan jadwal kerja, segera hubungi Admin.',
                'data'    => null,
            ]);
        }

        if ($checkLeaveStatus && $this->isUserOnLeave(Auth::user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat melakukan presensi karena sedang cuti.',
                'data'    => null,
            ]);
        }

        if ($schedule->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda diblokir dari presensi. Hubungi Admin.',
                'data'    => null,
            ], 403);
        }

        return null;
    }
}
