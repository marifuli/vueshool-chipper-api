<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Favorite;
use App\Notifications\NewPostNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PostNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_followers_are_notified_when_user_creates_post()
    {
        // Prevent actual notifications from being sent
        Notification::fake();

        // Create users
        $author = User::factory()->create();
        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();
        $nonFollower = User::factory()->create();

        // Set up followers (users who have favorited the author)
        Favorite::factory()->create([
            'user_id' => $follower1->id,
            'favoritable_id' => $author->id,
            'favoritable_type' => User::class,
        ]);

        Favorite::factory()->create([
            'user_id' => $follower2->id,
            'favoritable_id' => $author->id,
            'favoritable_type' => User::class,
        ]);

        // create post and followers
        $post = Post::factory()->create(['user_id' => $author->id]);

        $service = app(NotificationService::class);
        $service->notifyUsersAboutNewPost($post); // runs synchronously

        Notification::assertSentTo([$follower1, $follower2], NewPostNotification::class);

        // Assert notification was not sent to non-follower
        Notification::assertNotSentTo(
            [$nonFollower],
            NewPostNotification::class
        );
    }

    public function test_notification_contains_correct_post_data()
    {
        // Create users
        $author = User::factory()->create(['name' => 'Test Author']);
        $follower = User::factory()->create(['name' => 'Test Follower']);

        // Create a post
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Test Post Title',
            'body' => 'This is the content of the test post',
        ]);

        // Create a notification instance
        $notification = new NewPostNotification($post);

        // Get the mail representation
        $mail = $notification->toMail($follower);

        // Assert mail contains correct data
        $this->assertEquals("New Post from Test Author", $mail->subject);
        $this->assertStringContainsString("Test Author has published a new post", $mail->introLines[0]);
        $this->assertStringContainsString("Title: Test Post Title", $mail->introLines[1]);
        $this->assertStringContainsString("Content: This is the content of the test post", $mail->introLines[2]);
    }

    public function test_notifications_are_queued()
    {
        // Create users
        $author = User::factory()->create();
        $follower = User::factory()->create();

        // Set up follower
        Favorite::factory()->create([
            'user_id' => $follower->id,
            'favoritable_id' => $author->id,
            'favoritable_type' => User::class,
        ]);

        // Create a notification instance
        $post = Post::factory()->create(['user_id' => $author->id]);
        $notification = new NewPostNotification($post);

        // Assert the notification implements ShouldQueue
        $this->assertContains(
            'Illuminate\Contracts\Queue\ShouldQueue',
            class_implements($notification)
        );
    }
}
