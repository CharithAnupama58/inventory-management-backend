<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'capacity'      => $this->capacity,
            'item_count'    => $this->item_count,
            'is_full'       => $this->is_full,
            'usage_percent' => $this->usage_percent,
            'cupboard'      => $this->whenLoaded('cupboard', fn() => [
                'id'   => $this->cupboard->id,
                'name' => $this->cupboard->name,
                'code' => $this->cupboard->code,
            ]),
        ];
    }
}
