<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioRelax extends Model
{
    use HasFactory;

    protected $table = 'audio_relax';

    protected $fillable = [
        'audio_category_id',
        'title',
        'description',
        'audio_path',
        'duration_seconds',
        'thumbnail_path',
        'artist',
        'download_count',
        'play_count',
        'rating',
        'rating_count',
        'is_premium',
        'is_active',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'duration_seconds' => 'integer',
        'download_count' => 'integer',
        'play_count' => 'integer',
        'rating' => 'float',
        'rating_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that owns the audio.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AudioCategory::class, 'audio_category_id');
    }

    /**
     * Scope to get only active audio
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only free audio
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope to get only premium audio
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('audio_category_id', $categoryId);
    }

    /**
     * Scope to order by popularity
     */
    public function scopePopular($query)
    {
        return $query->orderBy('play_count', 'desc')
                    ->orderBy('rating', 'desc');
    }

    /**
     * Check if audio is from YouTube
     */
    public function getIsYoutubeAttribute(): bool
    {
        return str_contains($this->audio_path, 'youtube.com') || 
               str_contains($this->audio_path, 'youtu.be');
    }

    /**
     * Get YouTube video ID
     */
    public function getYoutubeVideoIdAttribute(): ?string
    {
        if (!$this->is_youtube) {
            return null;
        }

        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->audio_path, $matches);
        
        return $matches[1] ?? null;
    }

    /**
     * Get YouTube thumbnail URL
     */
    public function getYoutubeThumbnailAttribute(): ?string
    {
        $videoId = $this->youtube_video_id;
        if (!$videoId) {
            return $this->thumbnail_path;
        }

        return "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg";
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = intval($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function incrementPlayCount()
    {
        $this->increment('play_count');
    }
}
