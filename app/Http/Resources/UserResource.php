<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            "id"=> $this->id,
            "full_name"=> $this->full_name,
            "email"=> $this->email,
            "phone_number"=> $this->phone_number,
            "created_at"=> $this->created_at->format('Y-m-d H:i:s'),
            "updated_at"=> $this->updated_at->format('Y-m-d H:i:s'),
            "last_login_at"=> $this->last_login_at?->format('Y-m-d H:i:s'),
            "wishlist_items" => $this->whenLoaded("wishlistItems", function () {
                return $this->wishlistItems->map(function ($wishlistItem) {
                    if ($wishlistItem->variant_id) {
                        return new ProductVariantResource($wishlistItem->variant, true);
                    }else {
                        return new ProductResource($wishlistItem->product);
                    }
                });
            }),
        ];

        if ($this->is_agent) {
            $data['is_agent'] = true;
            $data['code'] = $this->code;
            $data['commission'] = $this->commission;
            $data['monthly_target'] = $this->monthly_target;
        }

        return $data;
    }
}
