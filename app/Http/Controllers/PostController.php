<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Requests\DestroyPostRequest;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;

/**
 * @group Posts
 *
 * API endpoints for managing posts
 */
class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->orderByDesc('created_at')->get();
        return PostResource::collection($posts);
    }

    public function store(CreatePostRequest $request, NotificationService $notificationService)
    {
        $user = $request->user();

        // Initialize post data
        $postData = [
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'user_id' => $user->id,
        ];

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('post-images', 'public');
            $postData['image_path'] = $imagePath;
        }

        // Create a new post
        $post = Post::create($postData);

        // Dispatch notification job asynchronously
        dispatch(function () use ($post, $notificationService) {
            $notificationService->notifyUsersAboutNewPost($post);
        })->afterResponse();

        return new PostResource($post);
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->update([
            'title' => $request->input('title'),
            'body' => $request->input('body'),
        ]);

        return new PostResource($post);
    }

    public function destroy(DestroyPostRequest $request, Post $post)
    {
        $post->favorites()->delete();
        $post->delete();
        return response()->noContent();
    }
}
