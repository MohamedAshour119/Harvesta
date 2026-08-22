<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndustrialCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    protected $include_image;
    public function __construct($resource, $include_image = true)
    {
        $this->resource = $resource;
        $this->include_image = $include_image;
    }
    public function toArray(Request $request): array
    {
        $image = $this->getFirstMediaUrl('industry_category_images');

        $data = [
            'id' => $this->id,
            'name'=> $this->name,
            'slug' => $this->slug,
            'products_count' => $this->products_count,
        ];

        if ($this->include_image) {
            $data['image'] = $image;
        }

        return $data;
    }
}
