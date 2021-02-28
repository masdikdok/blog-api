<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\PostSectionResource;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostImageResource;

class PostResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'status' => (int) $this->status,
            'image_url' => $image_url,
            'image_thumb_url' => $image_thumb_url,
            'sections' => PostSectionResource::collection($this->postSection()),
            'last_comments' => PostCommentResource::collection($this->whenLoaded('lastComment')),
            'images' => PostImageResource::collection($this->whenLoaded('postImage')),
            'created_at' => strtotime($this->created_at),
            'updated_at' => strtotime($this->updated_at),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
