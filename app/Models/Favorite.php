<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', // deprecated
        'user_id',
        'favoritable_type',
        'favoritable_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @deprecated Backward compatibility
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function favoritable()
    {
        return $this->morphTo();
    }

    // Scope to get post favorites (backward compatibility)
    public function scopePostFavorites($query)
    {
        return $query->where('favoritable_type', 'App\\Models\\Post');
    }

    // Scope to get user favorites
    public function scopeUserFavorites($query)
    {
        return $query->where('favoritable_type', 'App\\Models\\User');
    }
}
