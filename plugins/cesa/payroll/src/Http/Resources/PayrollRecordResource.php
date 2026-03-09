<?php

namespace Cesa\Payroll\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'user_id'                   => $this->user_id,
            'payroll_period_id'         => $this->payroll_period_id,
            'total_attendance_days'     => $this->total_attendance_days,
            'total_overtime_hours'      => $this->total_overtime_hours,
            'total_late_minutes'        => $this->total_late_minutes,
            'gross_salary'              => $this->gross_salary,
            'gross_salary_formatted'    => 'Rp '.number_format($this->gross_salary, 0, ',', '.'),
            'total_penalties'           => $this->total_penalties,
            'total_penalties_formatted' => 'Rp '.number_format($this->total_penalties, 0, ',', '.'),
            'net_salary'                => $this->net_salary,
            'net_salary_formatted'      => 'Rp '.number_format($this->net_salary, 0, ',', '.'),
            'details'                   => $this->details,
            'period'                    => $this->whenLoaded('period', function () {
                return [
                    'id'         => $this->period->id,
                    'name'       => $this->period->name,
                    'start_date' => $this->period->start_date ? $this->period->start_date->format('Y-m-d') : null,
                    'end_date'   => $this->period->end_date ? $this->period->end_date->format('Y-m-d') : null,
                    'status'     => $this->period->status,
                ];
            }),
            'created_at'                => $this->created_at->toIso8601String(),
            'updated_at'                => $this->updated_at->toIso8601String(),
        ];
    }
}
