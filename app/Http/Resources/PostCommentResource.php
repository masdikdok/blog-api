<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostCommentResource extends JsonResource
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
            'post_id' => $this->post_id,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
                'email' => $this->user->email
            ],
            'content' => $this->content,
            'child' => $this->collection($this->whenLoaded('child')),
            'created_at' => strtotime($this->created_at),
            'updated_at' => strtotime($this->updated_at),
        ];
    }
}
