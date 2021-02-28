<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => ! empty($this->image) ? asset("storage/images/category/{$this->image}") : null,
            'image_thumb_url' => ! empty($this->image) ? asset("storage/images/category/200/{$this->image}") : null,
            'no_order' => $this->no_order,
            'status' => $this->status,
            'child' => $this->collection($this->whenLoaded('child')),
            'created_at' => strtotime($this->created_at),
            'updated_at' => strtotime($this->updated_at),
        ];
    }
}
