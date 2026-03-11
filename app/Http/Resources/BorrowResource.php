<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BorrowResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'borrower_name'        => $this->borrower_name,
            'contact'              => $this->contact,
            'quantity'             => $this->quantity,
            'borrow_date'          => $this->borrow_date->toDateString(),
            'expected_return_date' => $this->expected_return_date->toDateString(),
            'actual_return_date'   => $this->actual_return_date?->toDateString(),
            'return_condition'     => $this->return_condition,
            'notes'                => $this->notes,
            'status'               => $this->status,
            'is_overdue'           => $this->isOverdue(),
            'days_overdue'         => $this->daysOverdue(),
            'days_until_due'       => $this->daysUntilDue(),
            'item'                 => $this->whenLoaded('item', fn() =>
                new ItemResource($this->item)
            ),
            'processed_by'         => $this->whenLoaded('processedBy', fn() => [
                'id'   => $this->processedBy->id,
                'name' => $this->processedBy->name,
            ]),
            'created_at'           => $this->created_at->toISOString(),
        ];
    }
}
