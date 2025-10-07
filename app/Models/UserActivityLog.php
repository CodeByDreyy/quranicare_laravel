<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_title',
        'reference_id',
        'reference_table',
        'duration_seconds',
        'completion_percentage',
        'metadata',
        'activity_date',
        'activity_time',
    ];

    protected $casts = [
        'metadata' => 'array',
        'completion_percentage' => 'decimal:2',
        'activity_date' => 'date',
        'activity_time' => 'datetime:H:i:s',
    ];

    // Activity type constants
    const TYPE_QURAN_READING = 'quran_reading';
    const TYPE_DZIKIR_SESSION = 'dzikir_session';
    const TYPE_BREATHING_EXERCISE = 'breathing_exercise';
    const TYPE_AUDIO_RELAXATION = 'audio_relaxation';
    const TYPE_JOURNAL_WRITING = 'journal_writing';
    const TYPE_QALBU_CHAT = 'qalbu_chat';
    const TYPE_PSYCHOLOGY_MATERIAL = 'psychology_material';
    const TYPE_APP_OPEN = 'app_open';
    const TYPE_MOOD_TRACKING = 'mood_tracking';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->where('activity_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('activity_date', now()->month)
                    ->whereYear('activity_date', now()->year);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    // Static methods for creating logs
    public static function logActivity($userId, $activityType, $data = [])
    {
        return self::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_title' => $data['title'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'reference_table' => $data['reference_table'] ?? null,
            'duration_seconds' => $data['duration'] ?? null,
            'completion_percentage' => $data['completion'] ?? 0,
            'metadata' => $data['metadata'] ?? null,
            'activity_date' => $data['date'] ?? today(),
            'activity_time' => $data['time'] ?? now()->format('H:i:s'),
        ]);
    }

    // Helper methods for Sakinah Tracker
    public static function getDailyActivities($userId, $date = null)
    {
        $date = $date ?? today();
        
        return self::where('user_id', $userId)
                   ->where('activity_date', $date)
                   ->orderBy('activity_time', 'desc')
                   ->get()
                   ->groupBy('activity_type');
    }

    public static function getMonthlyStats($userId, $year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return self::where('user_id', $userId)
                   ->whereYear('activity_date', $year)
                   ->whereMonth('activity_date', $month)
                   ->selectRaw('activity_date, activity_type, COUNT(*) as count, SUM(duration_seconds) as total_duration')
                   ->groupBy('activity_date', 'activity_type')
                   ->orderBy('activity_date', 'desc')
                   ->get();
    }

    public static function getActivityStreak($userId, $activityType)
    {
        $activities = self::where('user_id', $userId)
                         ->where('activity_type', $activityType)
                         ->selectRaw('DATE(activity_date) as date')
                         ->distinct()
                         ->orderBy('date', 'desc')
                         ->pluck('date')
                         ->map(function ($date) {
                             return Carbon::parse($date);
                         });

        if ($activities->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = today();

        foreach ($activities as $activityDate) {
            if ($activityDate->isSameDay($currentDate)) {
                $streak++;
                $currentDate = $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    // Accessors
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_seconds) return null;
        
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }
        return "{$seconds}s";
    }

    public function getActivityIconAttribute()
    {
        return match($this->activity_type) {
            self::TYPE_QURAN_READING => '📖',
            self::TYPE_DZIKIR_SESSION => '📿',
            self::TYPE_BREATHING_EXERCISE => '🫁',
            self::TYPE_AUDIO_RELAXATION => '🎵',
            self::TYPE_JOURNAL_WRITING => '✍️',
            self::TYPE_QALBU_CHAT => '💬',
            self::TYPE_PSYCHOLOGY_MATERIAL => '🧠',
            self::TYPE_APP_OPEN => '📱',
            self::TYPE_MOOD_TRACKING => '😊',
            default => '📋',
        };
    }
}