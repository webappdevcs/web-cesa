<?php

namespace Cesa\Kepegawaian\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistryEmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'employee_code' => $this->employee_code,
            'name'          => $this->name,
            'job_title'     => $this->job_title,
            'is_active'     => (bool) $this->is_active,
            'company'       => $this->whenLoaded('company', fn () => [
                'id'           => $this->company?->id,
                'company_code' => $this->company?->company_id,
                'name'         => $this->company?->name,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id'   => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'identifiers' => RegistryEmployeeIdentifierResource::collection(
                $this->whenLoaded('identifiers')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
