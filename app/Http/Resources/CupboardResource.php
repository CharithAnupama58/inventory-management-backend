<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CupboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'code'        => $this->code,
            'description' => $this->description,
            'location'    => $this->location,
            'color'       => $this->color,
            'bg_color'    => $this->bg_color,
            'places'      => $this->whenLoaded('places', fn() =>
                PlaceResource::collection($this->places)
            ),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
