<?php

namespace Cesa\Presensi\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Cesa\Presensi\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $query = Leave::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            $canViewAnyLeave = Auth::user()?->can('view_any_presensi_leave') ?? false;

            if (! $canViewAnyLeave) {
                $query->where('user_id', Auth::id());
            }

            $leaves = $query->get();

            return response()->json([
                'success' => true,
                'data'    => $leaves,
                'message' => 'Leaves retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving leaves',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type'       => 'required|in:Izin,Sakit,Cuti,Lainnya',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'reason'     => 'required|string',
                'file'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Check for overlapping leaves
            $overlap = Leave::where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) use ($request): void {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                        ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                        ->orWhere(function ($query) use ($request): void {
                            $query->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => [
                        'start_date' => ['You already have a leave request for this period.'],
                    ],
                ], 422);
            }

            $attachmentPath = null;
            if ($request->hasFile('file')) {
                $attachmentPath = $request->file('file')->store('presensi/attachments', 'public');
            }

            $leave = Leave::create([
                'user_id'    => Auth::id(),
                'type'       => $request->type,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'reason'     => $request->reason,
                'status'     => 'pending',
                'note'       => null,
                'attachment' => $attachmentPath,
            ]);

            $leave->load('user');

            return response()->json([
                'success' => true,
                'data'    => $leave,
                'message' => 'Leave request created successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating leave request',
            ], 500);
        }
    }
}
