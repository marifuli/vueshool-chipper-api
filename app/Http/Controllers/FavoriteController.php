<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\CreatePostFavoriteRequest;
use App\Http\Requests\DestroyPostFavoriteRequest;
use App\Http\Requests\CreateUserFavoriteRequest;
use App\Http\Requests\DestroyUserFavoriteRequest;
use Illuminate\Http\Response;
use App\Http\Resources\FavoriteResource;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $favoritedPosts = FavoriteResource::collection(
            $user->favorites()->where('favoritable_type', Post::class)->get()
        );
        $favoritedUsers = FavoriteResource::collection(
            $user->favorites()->where('favoritable_type', User::class)->get()
        );
        return response()->json([
            'data' => [
                'posts' => $favoritedPosts,
                'users' => $favoritedUsers
            ]
        ]);
    }

    public function storePostFavorite(CreatePostFavoriteRequest $request, Post $post)
    {
        $user = $request->user();

        // Check if favorite already exists
        $favorite = $user->favorites()
            ->where('favoritable_type', Post::class)
            ->where('favoritable_id', $post->id)
            ->first();

        if ($favorite) {
            return response()->json([
                'message' => 'Post already favorited'
            ], 409);
        }

        // Create new favorite
        $favorite = $user->favorites()->create([
            'favoritable_type' => Post::class,
            'favoritable_id' => $post->id,
            'post_id' => $post->id, // backward compatibility
        ]);

        return new FavoriteResource($favorite);
    }

    public function destroyPostFavorite(DestroyPostFavoriteRequest $request, Post $post)
    {
        $user = $request->user();

        // Find favorite
        $favorite = $user->favorites()
            ->where('favoritable_type', Post::class)
            ->where('favoritable_id', $post->id) // corrected from 'id'
            ->first();

        if (! $favorite) {
            return response()->json([
                'message' => 'Favorite not found'
            ], 404);
        }

        $favorite->delete();

        return response()->noContent();
    }

    public function storeUserFavorite(CreateUserFavoriteRequest $request, User $user)
    {
        $authUser = $request->user();

        $favorite = $authUser->favorites()
            ->where('favoritable_type', User::class)
            ->where('favoritable_id', $user->id)
            ->first();

        if ($favorite) {
            return response()->json([
                'message' => 'User already favorited'
            ], 409);
        }

        $favorite = $authUser->favorites()->create([
            'favoritable_type' => User::class,
            'favoritable_id' => $user->id,
        ]);

        return new FavoriteResource($favorite);
    }

    public function destroyUserFavorite(DestroyUserFavoriteRequest $request, User $user)
    {
        $favorite = $request->user()->favorites()
            ->where('favoritable_type', User::class)
            ->where('favoritable_id', $user->id)
            ->first();

        if (! $favorite) {
            return response()->json(['message' => 'Favorite not found'], 404);
        }

        $favorite->delete();

        return response()->noContent();
    }
}
