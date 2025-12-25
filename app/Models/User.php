<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // User AS ACTOR (what I favorite)
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // User AS TARGET (who favorited me)
    public function favoritedBy(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    // Helper method to get users that this user has favorited
    public function favoritedUsers()
    {
        return $this->belongsToMany(User::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favorites.favoritable_type', 'App\\Models\\User');
    }

    // Helper method to get posts that this user has favorited
    public function favoritedPosts()
    {
        return $this->belongsToMany(Post::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favorites.favoritable_type', 'App\\Models\\Post');
    }
}
