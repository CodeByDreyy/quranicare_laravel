<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreathingExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'breathing_category_id',
        'name',
        'description',
        'dzikir_text',
        'audio_path',
        'inhale_duration',
        'hold_duration',
        'exhale_duration',
        'total_cycle_duration',
        'default_repetitions',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function breathingCategory()
    {
        return $this->belongsTo(BreathingCategory::class);
    }

    public function breathingSessions()
    {
        return $this->hasMany(BreathingSession::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function calculateDuration($repetitions)
    {
        return $this->total_cycle_duration * $repetitions;
    }

    public function getRepetitionsForMinutes($minutes)
    {
        return ceil(($minutes * 60) / $this->total_cycle_duration);
    }
}
