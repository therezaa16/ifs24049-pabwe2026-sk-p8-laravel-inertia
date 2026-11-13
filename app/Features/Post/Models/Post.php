<?php

namespace App\Features\Post\Models;

use App\Features\Post\Enums\PostCategory;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'title', 'slug', 'category', 'excerpt', 'content', 'cover_image_path', 'is_published', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the user (author) that owns the post.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public (relative) URL for the cover image, or null when missing.
     */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path && Storage::disk('public')->exists($this->cover_image_path)) {
            return '/storage/'.ltrim($this->cover_image_path, '/');
        }

        return null;
    }

    /**
     * Estimated reading time in minutes (200 words per minute, minimum 1).
     */
    public function readingMinutes(): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags((string) $this->content)), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words) / 200));
    }

    /**
     * Compact representation used by list views (no full content).
     *
     * @return array<string, mixed>
     */
    public function toListItem(): array
    {
        $category = PostCategory::tryFrom((string) $this->category);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'category_label' => $category?->label() ?? $this->category,
            'category_badge' => ($category ?? PostCategory::UMUM)->badgeClass(),
            'excerpt' => $this->excerpt,
            'cover_url' => $this->coverImageUrl(),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_label' => $this->published_at?->translatedFormat('d M Y'),
            'updated_label' => $this->updated_at?->translatedFormat('d M Y H:i'),
            'reading_minutes' => $this->readingMinutes(),
            'author' => $this->relationLoaded('user') && $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
            ] : null,
        ];
    }

    /**
     * Full representation (including markdown content) used by detail views.
     *
     * @return array<string, mixed>
     */
    public function toDetail(): array
    {
        return [
            ...$this->toListItem(),
            'content' => $this->content,
            'created_label' => $this->created_at?->translatedFormat('d M Y H:i'),
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
