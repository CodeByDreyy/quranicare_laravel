<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreathingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'breathing_exercise_id',
        'planned_duration_minutes',
        'actual_duration_seconds',
        'completed_cycles',
        'completed',
        'notes',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breathingExercise()
    {
        return $this->belongsTo(BreathingExercise::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('completed', false);
    }

    // Helper methods
    public function markAsCompleted()
    {
        $this->update([
            'completed' => true,
            'completed_at' => now(),
            'actual_duration_seconds' => $this->started_at->diffInSeconds(now())
        ]);
    }

    public function getActualDurationMinutesAttribute()
    {
        return $this->actual_duration_seconds ? round($this->actual_duration_seconds / 60, 2) : 0;
    }
}
