<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoodStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'mood_counts',
        'dominant_mood',
        'mood_score',
        'total_entries',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'mood_counts' => 'array',
        'mood_score' => 'float'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByMoodScore($query, $minScore, $maxScore = null)
    {
        if ($maxScore) {
            return $query->whereBetween('mood_score', [$minScore, $maxScore]);
        }
        return $query->where('mood_score', '>=', $minScore);
    }

    // Helper methods
    public function getMoodPercentages()
    {
        if ($this->total_entries === 0) {
            return [];
        }

        $percentages = [];
        foreach ($this->mood_counts as $mood => $count) {
            $percentages[$mood] = round(($count / $this->total_entries) * 100, 2);
        }

        return $percentages;
    }

    public function getMoodScoreLabel()
    {
        if ($this->mood_score >= 4) {
            return 'Sangat Baik';
        } elseif ($this->mood_score >= 3) {
            return 'Baik';
        } elseif ($this->mood_score >= 2) {
            return 'Cukup';
        } else {
            return 'Perlu Perhatian';
        }
    }
}
