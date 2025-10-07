<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDoaDzikirSession extends Model
{
    use HasFactory;

    protected $table = 'user_doa_dzikir_sessions';

    protected $fillable = [
        'user_id',
        'doa_dzikir_id',
        'completed_count',
        'target_count',
        'duration_seconds',
        'mood_before',
        'mood_after',
        'notes',
        'completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the doa/dzikir for this session.
     */
    public function doaDzikir(): BelongsTo
    {
        return $this->belongsTo(DoaDzikir::class, 'doa_dzikir_id');
    }

    /**
     * Scope to get completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope to get sessions for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }

    /**
     * Scope to get sessions for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}