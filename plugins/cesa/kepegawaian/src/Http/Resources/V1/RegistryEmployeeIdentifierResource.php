<?php

namespace Cesa\Kepegawaian\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistryEmployeeIdentifierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'source_system'   => $this->source_system,
            'source_instance' => $this->source_instance,
            'identifier_type' => $this->identifier_type,
            'external_id'     => $this->external_id,
            'verified_at'     => $this->verified_at?->toISOString(),
            'last_seen_at'    => $this->last_seen_at?->toISOString(),
            'retired_at'      => $this->retired_at?->toISOString(),
        ];
    }
}
