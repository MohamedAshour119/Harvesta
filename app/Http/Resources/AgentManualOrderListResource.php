<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentManualOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'manual_sale_status' => $this->manual_sale_status,
            'status' => $this->status,
            'customer_name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
            'total' => (float) $this->total,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'proof_photo_url' => $this->getFirstMediaUrl('manual_sale_proof_photo'),
            'items_count' => $this->relationLoaded('items') ? $this->items->count() : null,
            'invoice_uuid' => $this->invoice_uuid,
        ];
    }
}
