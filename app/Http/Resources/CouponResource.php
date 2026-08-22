<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "value" => (float) $this->value,
            "usage_limit"=> $this->usage_limit,
            "used_count"=> $this->used_count,
            "expires_at"=> $this->expires_at,
            "status"=> $this->status,
        ];
    }
}
