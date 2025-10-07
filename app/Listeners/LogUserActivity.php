<?php

namespace App\Listeners;

use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    public function handle($event)
    {
        if (!Auth::check()) {
            return;
        }

        // Map old activity types to new format
        $activityTypeMap = [
            'dzikir_started' => UserActivityLog::TYPE_DZIKIR_SESSION,
            'dzikir_completed' => UserActivityLog::TYPE_DZIKIR_SESSION,
            'quran_reading_started' => UserActivityLog::TYPE_QURAN_READING,
            'quran_reading_completed' => UserActivityLog::TYPE_QURAN_READING,
            'breathing_exercise_started' => UserActivityLog::TYPE_BREATHING_EXERCISE,
            'breathing_exercise_completed' => UserActivityLog::TYPE_BREATHING_EXERCISE,
        ];

        $activityType = $activityTypeMap[$event->activityType] ?? $event->activityType;
        $data = $event->activityData ?? [];

        UserActivityLog::logActivity(Auth::id(), $activityType, [
            'title' => $data['dzikir_name'] ?? $data['surah_name'] ?? $data['title'] ?? null,
            'reference_id' => $data['session_id'] ?? null,
            'reference_table' => $this->getReferenceTable($activityType),
            'duration' => $data['duration_seconds'] ?? null,
            'completion' => $this->getCompletionPercentage($event->activityType, $data),
            'metadata' => $data,
            'date' => today(),
            'time' => now()->format('H:i:s'),
        ]);
    }

    private function getReferenceTable($activityType)
    {
        return match($activityType) {
            UserActivityLog::TYPE_DZIKIR_SESSION => 'user_doa_dzikir_sessions',
            UserActivityLog::TYPE_QURAN_READING => 'quran_reading_sessions',
            UserActivityLog::TYPE_BREATHING_EXERCISE => 'breathing_sessions',
            UserActivityLog::TYPE_AUDIO_RELAXATION => 'audio_listening_sessions',
            UserActivityLog::TYPE_JOURNAL_WRITING => 'journals',
            default => null,
        };
    }

    private function getCompletionPercentage($eventType, $data)
    {
        if (str_ends_with($eventType, '_completed')) {
            return 100;
        }
        
        if (str_ends_with($eventType, '_started')) {
            return 0;
        }
        
        return $data['completion_percentage'] ?? 0;
    }
}