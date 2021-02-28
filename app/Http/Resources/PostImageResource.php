<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $image_url = ! empty($this->mainImage->image) ? asset("storage/images/post/{$this->id}/{$this->image}") : null;
        $image_thumb_url = ! empty($this->mainImage->image) ? asset("storage/images/post/{$this->id}/{$this->image}") : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'note' => $this->note,
            'image_url' => $image_url,
            'image_thumb_url' => $image_thumb_url,
        ];
    }
}
