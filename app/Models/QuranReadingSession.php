<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranReadingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quran_surah_id',
        'ayah_from',
        'ayah_to',
        'reading_duration_seconds',
        'progress_percentage',
        'reading_type',
        'mood_before',
        'mood_after',
        'reflection',
        'bookmarked_ayahs',
        'completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'bookmarked_ayahs' => 'array',
        'progress_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'quran_surah_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeByReadingType($query, $type)
    {
        return $query->where('reading_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getFormattedDurationAttribute()
    {
        $minutes = floor($this->reading_duration_seconds / 60);
        $seconds = $this->reading_duration_seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }
        return "{$seconds}s";
    }

    public function getAyahRangeAttribute()
    {
        if ($this->ayah_from && $this->ayah_to) {
            if ($this->ayah_from === $this->ayah_to) {
                return "Ayah {$this->ayah_from}";
            }
            return "Ayah {$this->ayah_from}-{$this->ayah_to}";
        }
        return "Full Surah";
    }
}