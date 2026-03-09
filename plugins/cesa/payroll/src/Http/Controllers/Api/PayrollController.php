<?php

namespace Cesa\Payroll\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Cesa\Payroll\Http\Resources\PayrollRecordResource;
use Cesa\Payroll\Models\PayrollRecord;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $records = PayrollRecord::with('period')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return PayrollRecordResource::collection($records);
    }

    public function show($id)
    {
        $record = PayrollRecord::with('period')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return new PayrollRecordResource($record);
    }
}
