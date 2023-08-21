<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_not_favorite_a_post_as_guest()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertForbidden();
    }

    /** @test */
    public function it_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_remove_a_post_from_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }
}
