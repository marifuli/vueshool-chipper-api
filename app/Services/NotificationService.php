<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notifications to users who have favorited the post author.
     *
     * @param  \App\Models\Post  $post
     * @return void
     */
    public function notifyUsersAboutNewPost(Post $post)
    {
        try {
            // Get the author of the post
            $author = $post->user;

            // Get all favorites for this author
            $favorites = $author->favoritedBy()->with('user')->get();
            // Send notification to each follower
            foreach ($favorites as $favorite) {
                $favorite->user->notify(new NewPostNotification($post));
            }

        } catch (\Exception $e) {
            Log::error("Failed to send post notifications: {$e->getMessage()}", [
                'post_id' => $post->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
