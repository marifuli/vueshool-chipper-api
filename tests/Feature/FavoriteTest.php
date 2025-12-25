<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    /* ===== POST FAVORITES TESTS ===== */

    public function test_guest_cannot_favorite_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.post.store', $post))
            ->assertStatus(401);
    }

    public function test_user_can_favorite_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.post.store', $post))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Post::class,
            'favoritable_id' => $post->id,
        ]);

        // Backward compatibility
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_remove_post_favorite()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.post.store', $post))
            ->assertCreated();

        $this->actingAs($user)
            ->deleteJson(route('favorites.post.destroy', $post))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Post::class,
            'favoritable_id' => $post->id,
        ]);
    }

    public function test_user_cannot_remove_nonfavorited_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.post.destroy', $post))
            ->assertNotFound();
    }

    /* ===== USER FAVORITES TESTS ===== */

    public function test_guest_cannot_favorite_user()
    {
        $userToFavorite = User::factory()->create();

        $this->postJson(route('favorites.user.store', $userToFavorite))
            ->assertStatus(401);
    }

    public function test_user_can_favorite_another_user()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $userToFavorite->id,
        ]);
    }

    public function test_user_cannot_favorite_self()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $user))
            ->assertStatus(422)
            ->assertJsonValidationErrors('user');

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $user->id,
        ]);
    }

    public function test_user_cannot_favorite_same_user_twice()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertStatus(409);

        $this->assertEquals(1, $user->favorites()
            ->where('favoritable_type', User::class)
            ->where('favoritable_id', $userToFavorite->id)
            ->count());
    }

    public function test_user_can_remove_user_favorite()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertCreated();

        $this->actingAs($user)
            ->deleteJson(route('favorites.user.destroy', $userToFavorite))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $userToFavorite->id,
        ]);
    }

    public function test_user_cannot_remove_nonfavorited_user()
    {
        $user = User::factory()->create();
        $userToUnfavorite = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.user.destroy', $userToUnfavorite))
            ->assertNotFound();
    }

    /* ===== MIXED FAVORITES TESTS ===== */

    public function test_user_can_favorite_posts_and_users_independently()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.post.store', $post))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Post::class,
            'favoritable_id' => $post->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $userToFavorite->id,
        ]);

        $this->assertEquals(2, $user->favorites()->count());
    }

    public function test_user_can_independently_remove_post_and_user_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.post.store', $post))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertCreated();

        $this->actingAs($user)
            ->deleteJson(route('favorites.post.destroy', $post))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Post::class,
            'favoritable_id' => $post->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => User::class,
            'favoritable_id' => $userToFavorite->id,
        ]);
    }

    /* ===== RELATIONSHIP TESTS ===== */

    public function test_user_favorite_relationships()
    {
        $user = User::factory()->create();
        $postAuthor = User::factory()->create();
        $post = Post::factory()->for($postAuthor, 'user')->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.post.store', $post))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.user.store', $userToFavorite))
            ->assertCreated();

        $user->refresh();

        $favoritedPosts = $user->favoritedPosts;
        $this->assertCount(1, $favoritedPosts);
        $this->assertEquals($post->id, $favoritedPosts->first()->id);

        $favoritedUsers = $user->favoritedUsers;
        $this->assertCount(1, $favoritedUsers);
        $this->assertEquals($userToFavorite->id, $favoritedUsers->first()->id);
    }
}
