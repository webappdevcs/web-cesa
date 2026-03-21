<?php

namespace Cesa\Presensi\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Overtime;
use Cesa\Presensi\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $overtimes = Overtime::with('user')
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $overtimes,
                'message' => 'Overtimes retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving overtimes',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                /**
                 * Date
                 *
                 * @example 2024-10-25
                 */
                'date' => 'required|date',
                /**
                 * Start Time
                 *
                 * @example 18:00
                 */
                'start_time' => 'required|string',
                /**
                 * End Time
                 *
                 * @example 20:00
                 */
                'end_time' => 'required|string',
                /**
                 * Reason
                 *
                 * @example Lembur project A
                 */
                'reason' => 'required|string',
                'file'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $attachmentPath = null;
            if ($request->hasFile('file')) {
                $attachmentPath = $request->file('file')->store('presensi/attachments', 'public');
            }

            $startTime = $this->normalizeTime($request->start_time);
            $endTime = $this->normalizeTime($request->end_time);

            if (! $startTime || ! $endTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'start_time' => ['Start time format must be HH:MM or HH:MM:SS.'],
                        'end_time'   => ['End time format must be HH:MM or HH:MM:SS.'],
                    ],
                ], 422);
            }

            $startAt = Carbon::createFromFormat('H:i:s', $startTime);
            $endAt = Carbon::createFromFormat('H:i:s', $endTime);
            if ($endAt->lessThanOrEqualTo($startAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'end_time' => ['End time must be after start time.'],
                    ],
                ], 422);
            }

            $requestDate = Carbon::parse($request->date)->toDateString();
            $requestDateAt = Carbon::parse($request->date)->startOfDay();
            $todayAt = Carbon::today();
            $attendance = Attendance::query()
                ->where('user_id', Auth::id())
                ->forAttendanceDate($requestDate)
                ->orderByAttendanceDate()
                ->first();

            $schedule = Schedule::resolveActiveForUser(Auth::id(), Carbon::parse($request->date));

            if (
                $requestDateAt->greaterThanOrEqualTo($todayAt)
                && (! $schedule || $schedule->is_banned || ! $schedule->shift)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'User belum mendapatkan jadwal kerja, segera hubungi Admin.',
                    'data'    => null,
                ], 422);
            }

            $onLeave = Leave::where('user_id', Auth::id())
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $requestDate)
                ->whereDate('end_date', '>=', $requestDate)
                ->exists();

            if ($onLeave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat mengajukan lembur karena sedang cuti.',
                    'data'    => null,
                ], 422);
            }

            if ($requestDateAt->lessThan($todayAt) && ! $attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'date' => ['Attendance record not found for the requested date.'],
                    ],
                ], 422);
            }

            $effectiveScheduleStart = $attendance?->schedule_start_time ?? $schedule?->shift?->start_time;
            $effectiveScheduleEnd = $attendance?->schedule_end_time ?? $schedule?->shift?->end_time;

            $scheduleStartTime = $this->normalizeTime($effectiveScheduleStart);
            $scheduleEndTime = $this->normalizeTime($effectiveScheduleEnd);

            if (! $scheduleStartTime || ! $scheduleEndTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'start_time' => ['Schedule time is invalid.'],
                    ],
                ], 422);
            }

            $scheduleStartAt = Carbon::createFromFormat('H:i:s', $scheduleStartTime);
            $scheduleEndAt = Carbon::createFromFormat('H:i:s', $scheduleEndTime);

            $outsideShift = $endAt->lessThanOrEqualTo($scheduleStartAt)
                || $startAt->greaterThanOrEqualTo($scheduleEndAt);
            if (! $outsideShift) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'start_time' => ['Overtime must be outside schedule hours.'],
                    ],
                ], 422);
            }

            $earlyOvertime = $endAt->lessThanOrEqualTo($scheduleStartAt);
            $afterOvertime = $startAt->greaterThanOrEqualTo($scheduleEndAt);

            if ($requestDateAt->lessThan($todayAt) && $earlyOvertime) {
                $attendanceStartTime = $this->normalizeTime($attendance->start_time);
                if (! $attendanceStartTime) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors'  => [
                            'start_time' => ['Attendance start time is invalid.'],
                        ],
                    ], 422);
                }

                $attendanceStartAt = Carbon::createFromFormat('H:i:s', $attendanceStartTime);
                if ($attendanceStartAt->greaterThan($startAt)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors'  => [
                            'start_time' => ['Overtime start time must be before or equal to attendance start time.'],
                        ],
                    ], 422);
                }
            }

            if ($requestDateAt->lessThan($todayAt) && $afterOvertime) {
                $attendanceEndTime = $this->normalizeTime($attendance->end_time);
                if (! $attendanceEndTime) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors'  => [
                            'end_time' => ['Attendance end time is invalid.'],
                        ],
                    ], 422);
                }

                $attendanceEndAt = Carbon::createFromFormat('H:i:s', $attendanceEndTime);
                if ($attendanceEndAt->lessThan($endAt)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors'  => [
                            'end_time' => ['Overtime end time exceeds attendance end time.'],
                        ],
                    ], 422);
                }
            }

            $overlap = Overtime::where('user_id', Auth::id())
                ->whereDate('date', $requestDate)
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
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'start_time' => ['Overtime overlaps with an existing request.'],
                    ],
                ], 422);
            }

            $overtime = Overtime::create([
                'user_id'    => Auth::id(),
                'date'       => $requestDate,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'reason'     => $request->reason,
                'status'     => 'pending',
                'attachment' => $attachmentPath,
            ]);

            $overtime->load('user');

            return response()->json([
                'success' => true,
                'data'    => $overtime,
                'message' => 'Overtime request created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating overtime request',
            ], 500);
        }
    }

    private function normalizeTime(?string $value): ?string
    {
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
