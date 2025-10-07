<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quran_ayah_id',
        'title',
        'content',
        'reflection',
        'mood_after',
        'tags',
        'is_private',
        'is_favorite',
        'journal_date'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_favorite' => 'boolean',
        'is_private' => 'boolean',
        'journal_date' => 'date'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ayah()
    {
        return $this->belongsTo(QuranAyah::class, 'quran_ayah_id');
    }

    // Scopes
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeByMood($query, $mood)
    {
        return $query->where('mood_after', $mood);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getExcerptAttribute($length = 100)
    {
        return strlen($this->content) > $length 
            ? substr($this->content, 0, $length) . '...' 
            : $this->content;
    }

    public function getAyahReferenceAttribute()
    {
        if ($this->ayah && $this->ayah->surah) {
            return $this->ayah->surah->name_indonesian . ' (' . $this->ayah->surah->number . ':' . $this->ayah->number . ')';
        }
        return null;
    }
}
