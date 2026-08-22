<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentManualOrderResource extends JsonResource
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
            'payment_status' => $this->payment_status,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'total' => (float) $this->total,
            'customer' => [
                'user_id' => $this->user_id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'company_name' => $this->company_name,
                'address' => $this->address,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
            ],
            'special_sales_options' => $this->special_sales_options,
            'proof_photo_url' => $this->getFirstMediaUrl('manual_sale_proof_photo'),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_variant_id' => $item->product_variant_id,
                        'variant_value' => $item->variant?->variant_value,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'line_total' => (float) $item->total,
                    ];
                })->values();
            }),
        ];
    }
}
