<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mood extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mood_type',
        'notes',
        'mood_date',
        'mood_time'
    ];

    protected $casts = [
        'mood_date' => 'date',
        'mood_time' => 'datetime:H:i:s',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('mood_date', $date);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByMoodType($query, $moodType)
    {
        return $query->where('mood_type', $moodType);
    }
}
