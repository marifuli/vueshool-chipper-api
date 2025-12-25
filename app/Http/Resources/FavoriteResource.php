<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->favoritable_type === Post::class) {
            return [
                'id' => $this->favoritable->id,
                'title' => $this->favoritable->title,
                'body' => $this->favoritable->body,
                'user' => $this->favoritable->user ? [
                    'id' => $this->favoritable->user->id,
                    'name' => $this->favoritable->user->name,
                ] : null,
            ];
        }

        if ($this->favoritable_type === User::class) {
            return [
                'id' => $this->favoritable->id,
                'name' => $this->favoritable->name,
            ];
        }

        return parent::toArray($request);
    }
}
