<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Post;
use App\Models\Favorite;
use App\Notifications\NewPostNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_sent_to_followers_only()
    {
        Notification::fake();

        $author = User::factory()->create();
        $followers = User::factory()->count(2)->create();
        $nonFollower = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $author->id,
        ]);

        // Make followers favorite the author
        foreach ($followers as $follower) {
            Favorite::factory()->create([
                'user_id' => $follower->id,
                'favoritable_id' => $author->id,
                'favoritable_type' => User::class,
            ]);
        }

        $service = new NotificationService();
        $service->notifyUsersAboutNewPost($post);

        // Assert notifications sent to followers
        Notification::assertSentTo($followers, NewPostNotification::class);

        // Assert notifications not sent to non-follower or author
        Notification::assertNotSentTo([$nonFollower, $author], NewPostNotification::class);
    }

    public function test_graceful_exception_handling_does_not_bubble_up()
    {
        Notification::fake();

        // Make Notification::send throw an exception
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Test exception'));

        $author = User::factory()->create();
        $follower = User::factory()->create();

        Favorite::factory()->create([
            'user_id' => $follower->id,
            'favoritable_id' => $author->id,
            'favoritable_type' => User::class,
        ]);

        $post = Post::factory()->create(['user_id' => $author->id]);

        $service = new NotificationService();

        // The service should catch exceptions internally, so this will not throw
        $this->expectNotToPerformAssertions();
        $service->notifyUsersAboutNewPost($post);
    }

    public function test_no_notifications_sent_if_no_followers()
    {
        Notification::fake();

        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $service = new NotificationService();
        $service->notifyUsersAboutNewPost($post);

        Notification::assertNothingSent();
    }
}
