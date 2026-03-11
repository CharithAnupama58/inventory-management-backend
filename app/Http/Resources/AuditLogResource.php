<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'action'         => $this->action,
            'entity_type'    => $this->entity_type,
            'entity_id'      => $this->entity_id,
            'entity_name'    => $this->entity_name,
            'previous_value' => $this->previous_value,
            'new_value'      => $this->new_value,
            'performed_by'   => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role,
            ]),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
