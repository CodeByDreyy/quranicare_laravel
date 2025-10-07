<?php

namespace App\Services;

use App\Models\UserActivityLog;
use App\Models\QuranReadingSession;
use App\Models\UserDzikirSession;
use App\Models\BreathingSession;
use App\Models\AudioListeningSession;
use App\Models\Journal;
use App\Models\QalbuConversation;
use App\Models\UserMaterialProgress;
use Carbon\Carbon;

class SakinahTrackerService
{
    /**
     * Get daily recap for a specific date
     */
    public function getDailyRecap($userId, $date = null)
    {
        $date = $date ?? today();
        
        // Get activities from activity log
        $activities = UserActivityLog::getDailyActivities($userId, $date);
        
        // Get additional data from existing tables (if not logged yet)
        $recap = [
            'date' => $date,
            'activities' => $activities,
            'summary' => [
                'total_activities' => $activities->flatten()->count(),
                'total_duration' => $activities->flatten()->sum('duration_seconds'),
                'activity_types' => $activities->keys()->toArray(),
                'completion_rate' => $this->calculateDailyCompletionRate($activities),
                'mood_trend' => $this->extractMoodTrend($activities),
            ],
            'streaks' => $this->getActivityStreaks($userId),
            'goals_achieved' => $this->checkDailyGoals($activities),
        ];

        return $recap;
    }

    /**
     * Get monthly statistics
     */
    public function getMonthlyStats($userId, $year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        $monthlyData = UserActivityLog::getMonthlyStats($userId, $year, $month);
        
        // Group by date for calendar view
        $calendar = [];
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // Initialize all dates in month
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $calendar[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->day,
                'activities' => [],
                'total_duration' => 0,
                'activity_count' => 0,
                'has_activity' => false,
            ];
        }
        
        // Fill with actual data
        foreach ($monthlyData as $data) {
            $dateKey = $data->activity_date->format('Y-m-d');
            if (isset($calendar[$dateKey])) {
                $calendar[$dateKey]['activities'][] = [
                    'type' => $data->activity_type,
                    'count' => $data->count,
                    'duration' => $data->total_duration,
                ];
                $calendar[$dateKey]['total_duration'] += $data->total_duration;
                $calendar[$dateKey]['activity_count'] += $data->count;
                $calendar[$dateKey]['has_activity'] = true;
            }
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => Carbon::create($year, $month, 1)->format('F'),
            'calendar' => array_values($calendar),
            'summary' => $this->getMonthSummary($monthlyData),
        ];
    }

    /**
     * Log activity when user performs an action
     */
    public function logActivity($userId, $activityType, $data = [])
    {
        // Auto-generate title based on activity type and data
        $title = $this->generateActivityTitle($activityType, $data);
        
        $logData = array_merge($data, [
            'title' => $title,
            'date' => $data['date'] ?? today(),
            'time' => $data['time'] ?? now()->format('H:i:s'),
        ]);

        return UserActivityLog::logActivity($userId, $activityType, $logData);
    }

    /**
     * Sync existing session data to activity logs (one-time migration)
     */
    public function syncExistingData($userId)
    {
        // Sync Quran reading (if any custom logic exists)
        // For now, we'll just ensure future reads are logged
        
        // Sync Dzikir sessions
        $dzikirSessions = UserDzikirSession::where('user_id', $userId)
            ->whereDoesntHave('activityLog')
            ->get();
            
        foreach ($dzikirSessions as $session) {
            $this->logActivity($userId, UserActivityLog::TYPE_DZIKIR_SESSION, [
                'title' => $session->doaDzikir->nama ?? 'Dzikir Session',
                'reference_id' => $session->id,
                'reference_table' => 'user_dzikir_sessions',
                'duration' => $session->duration_seconds,
                'completion' => $session->completed ? 100 : 0,
                'metadata' => [
                    'completed_count' => $session->completed_count,
                    'target_count' => $session->target_count,
                    'mood_before' => $session->mood_before,
                    'mood_after' => $session->mood_after,
                ],
                'date' => $session->created_at->toDateString(),
                'time' => $session->created_at->format('H:i:s'),
            ]);
        }

        // Sync other sessions similarly...
        $this->syncBreathingSessions($userId);
        $this->syncAudioSessions($userId);
        $this->syncJournals($userId);
        $this->syncQalbuChats($userId);
        $this->syncPsychologyProgress($userId);
    }

    /**
     * Check if user has activities today (for notifications)
     */
    public function hasActivityToday($userId)
    {
        return UserActivityLog::where('user_id', $userId)
                            ->today()
                            ->exists();
    }

    /**
     * Get activity streaks for motivation
     */
    public function getActivityStreaks($userId)
    {
        return [
            'quran_reading' => UserActivityLog::getActivityStreak($userId, UserActivityLog::TYPE_QURAN_READING),
            'dzikir' => UserActivityLog::getActivityStreak($userId, UserActivityLog::TYPE_DZIKIR_SESSION),
            'breathing' => UserActivityLog::getActivityStreak($userId, UserActivityLog::TYPE_BREATHING_EXERCISE),
            'journal' => UserActivityLog::getActivityStreak($userId, UserActivityLog::TYPE_JOURNAL_WRITING),
            'overall' => $this->getOverallStreak($userId),
        ];
    }

    // Private helper methods
    private function calculateDailyCompletionRate($activities)
    {
        $completed = $activities->flatten()->where('completion_percentage', '>=', 100)->count();
        $total = $activities->flatten()->count();
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    private function extractMoodTrend($activities)
    {
        $moods = [];
        
        foreach ($activities->flatten() as $activity) {
            if ($activity->metadata && isset($activity->metadata['mood_after'])) {
                $moods[] = $activity->metadata['mood_after'];
            }
        }

        if (empty($moods)) return null;
        
        // Return most frequent mood
        $moodCounts = array_count_values($moods);
        arsort($moodCounts);
        
        return array_key_first($moodCounts);
    }

    private function checkDailyGoals($activities)
    {
        // Define daily goals
        $goals = [
            'quran_reading' => 1, // At least 1 Quran reading
            'dzikir_session' => 1, // At least 1 dzikir
            'journal_writing' => 1, // At least 1 journal entry
        ];

        $achieved = [];
        
        foreach ($goals as $activityType => $targetCount) {
            $actualCount = $activities->get($activityType)?->count() ?? 0;
            $achieved[$activityType] = $actualCount >= $targetCount;
        }

        return $achieved;
    }

    private function getMonthSummary($monthlyData)
    {
        $totalActivities = $monthlyData->sum('count');
        $totalDuration = $monthlyData->sum('total_duration');
        $activeDays = $monthlyData->groupBy('activity_date')->count();
        
        $activityBreakdown = $monthlyData->groupBy('activity_type')->map(function ($group) {
            return [
                'count' => $group->sum('count'),
                'duration' => $group->sum('total_duration'),
                'days' => $group->groupBy('activity_date')->count(),
            ];
        });

        return [
            'total_activities' => $totalActivities,
            'total_duration_seconds' => $totalDuration,
            'total_duration_formatted' => $this->formatDuration($totalDuration),
            'active_days' => $activeDays,
            'activity_breakdown' => $activityBreakdown,
        ];
    }

    private function generateActivityTitle($activityType, $data)
    {
        return match($activityType) {
            UserActivityLog::TYPE_QURAN_READING => $data['surah_name'] ?? 'Al-Quran Reading',
            UserActivityLog::TYPE_DZIKIR_SESSION => $data['dzikir_name'] ?? 'Dzikir Session',
            UserActivityLog::TYPE_BREATHING_EXERCISE => $data['exercise_name'] ?? 'Breathing Exercise',
            UserActivityLog::TYPE_AUDIO_RELAXATION => $data['audio_name'] ?? 'Audio Relaxation',
            UserActivityLog::TYPE_JOURNAL_WRITING => $data['journal_title'] ?? 'Journal Entry',
            UserActivityLog::TYPE_QALBU_CHAT => 'QalbuChat Session',
            UserActivityLog::TYPE_PSYCHOLOGY_MATERIAL => $data['material_name'] ?? 'Psychology Material',
            default => 'Activity',
        };
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$secs}s";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }
        return "{$secs}s";
    }

    private function getOverallStreak($userId)
    {
        // Get dates with any activity
        $activityDates = UserActivityLog::where('user_id', $userId)
                                       ->selectRaw('DATE(activity_date) as date')
                                       ->distinct()
                                       ->orderBy('date', 'desc')
                                       ->pluck('date')
                                       ->map(fn($date) => Carbon::parse($date));

        if ($activityDates->isEmpty()) return 0;

        $streak = 0;
        $currentDate = today();

        foreach ($activityDates as $activityDate) {
            if ($activityDate->isSameDay($currentDate)) {
                $streak++;
                $currentDate = $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    private function syncBreathingSessions($userId)
    {
        $sessions = BreathingSession::where('user_id', $userId)->get();
        foreach ($sessions as $session) {
            $this->logActivity($userId, UserActivityLog::TYPE_BREATHING_EXERCISE, [
                'title' => $session->breathingExercise->name ?? 'Breathing Exercise',
                'reference_id' => $session->id,
                'reference_table' => 'breathing_sessions',
                'duration' => $session->actual_duration_seconds,
                'completion' => $session->completed ? 100 : 0,
                'date' => $session->created_at->toDateString(),
                'time' => $session->created_at->format('H:i:s'),
            ]);
        }
    }

    private function syncAudioSessions($userId)
    {
        $sessions = AudioListeningSession::where('user_id', $userId)->get();
        foreach ($sessions as $session) {
            $this->logActivity($userId, UserActivityLog::TYPE_AUDIO_RELAXATION, [
                'title' => $session->audioRelax->title ?? 'Audio Relaxation',
                'reference_id' => $session->id,
                'reference_table' => 'audio_listening_sessions',
                'duration' => $session->listened_duration_seconds,
                'completion' => $session->progress_percentage,
                'date' => $session->created_at->toDateString(),
                'time' => $session->created_at->format('H:i:s'),
            ]);
        }
    }

    private function syncJournals($userId)
    {
        $journals = Journal::where('user_id', $userId)->get();
        foreach ($journals as $journal) {
            $this->logActivity($userId, UserActivityLog::TYPE_JOURNAL_WRITING, [
                'title' => $journal->title,
                'reference_id' => $journal->id,
                'reference_table' => 'journals',
                'completion' => 100,
                'metadata' => [
                    'mood_after' => $journal->mood_after,
                    'tags' => $journal->tags,
                ],
                'date' => $journal->journal_date->toDateString(),
                'time' => $journal->created_at->format('H:i:s'),
            ]);
        }
    }

    private function syncQalbuChats($userId)
    {
        $conversations = QalbuConversation::where('user_id', $userId)->get();
        foreach ($conversations as $conversation) {
            $this->logActivity($userId, UserActivityLog::TYPE_QALBU_CHAT, [
                'title' => 'QalbuChat Session',
                'reference_id' => $conversation->id,
                'reference_table' => 'qalbu_conversations',
                'completion' => 100,
                'metadata' => [
                    'conversation_type' => $conversation->conversation_type,
                    'user_emotion' => $conversation->user_emotion,
                ],
                'date' => $conversation->created_at->toDateString(),
                'time' => $conversation->created_at->format('H:i:s'),
            ]);
        }
    }

    private function syncPsychologyProgress($userId)
    {
        $progress = UserMaterialProgress::where('user_id', $userId)->get();
        foreach ($progress as $material) {
            if ($material->progress_percentage > 0) {
                $this->logActivity($userId, UserActivityLog::TYPE_PSYCHOLOGY_MATERIAL, [
                    'title' => $material->psychologyMaterial->title ?? 'Psychology Material',
                    'reference_id' => $material->id,
                    'reference_table' => 'user_material_progress',
                    'duration' => $material->time_spent_seconds,
                    'completion' => $material->progress_percentage,
                    'date' => $material->last_accessed_at->toDateString(),
                    'time' => $material->last_accessed_at->format('H:i:s'),
                ]);
            }
        }
    }
}