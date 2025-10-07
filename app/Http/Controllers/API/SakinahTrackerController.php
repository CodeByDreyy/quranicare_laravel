<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SakinahTrackerService;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SakinahTrackerController extends Controller
{
    protected SakinahTrackerService $trackerService;

    public function __construct(SakinahTrackerService $trackerService)
    {
        $this->trackerService = $trackerService;
    }

    /**
     * Get daily recap for today or specific date
     */
    public function getDailyRecap(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $date = $request->get('date') ? Carbon::parse($request->get('date')) : today();
            
            $recap = $this->trackerService->getDailyRecap($userId, $date);
            
            return response()->json([
                'success' => true,
                'message' => 'Daily recap retrieved successfully',
                'data' => $recap,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get daily recap: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get monthly statistics for calendar view
     */
    public function getMonthlyStats(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            
            $stats = $this->trackerService->getMonthlyStats($userId, $year, $month);
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly statistics retrieved successfully',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monthly stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log a new activity
     */
    public function logActivity(Request $request): JsonResponse
    {
        $request->validate([
            'activity_type' => 'required|in:quran_reading,dzikir_session,breathing_exercise,audio_relaxation,journal_writing,qalbu_chat,psychology_material,app_open,mood_tracking',
            'activity_title' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'reference_table' => 'nullable|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'completion_percentage' => 'nullable|numeric|between:0,100',
            'metadata' => 'nullable|array',
            'activity_date' => 'nullable|date',
        ]);

        try {
            $userId = auth()->id();
            
            $activityLog = $this->trackerService->logActivity(
                $userId,
                $request->activity_type,
                $request->only([
                    'title' => 'activity_title',
                    'reference_id',
                    'reference_table',
                    'duration' => 'duration_seconds',
                    'completion' => 'completion_percentage',
                    'metadata',
                    'date' => 'activity_date',
                ])
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Activity logged successfully',
                'activity' => $activityLog,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log activity: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity streaks
     */
    public function getStreaks(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $streaks = $this->trackerService->getActivityStreaks($userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Activity streaks retrieved successfully',
                'streaks' => $streaks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get streaks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity history with filters
     */
    public function getActivityHistory(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $query = UserActivityLog::where('user_id', $userId);

            // Apply filters
            if ($request->has('activity_type')) {
                $query->byType($request->activity_type);
            }

            if ($request->has('date_from')) {
                $query->where('activity_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('activity_date', '<=', $request->date_to);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $activities = $query->orderBy('activity_date', 'desc')
                               ->orderBy('activity_time', 'desc')
                               ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Activity history retrieved successfully',
                'activities' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get activity history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard summary (for homepage widgets)
     */
    public function getDashboardSummary(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $today = $this->trackerService->getDailyRecap($userId, today());
            $streaks = $this->trackerService->getActivityStreaks($userId);
            
            // This week summary
            $thisWeekActivities = UserActivityLog::where('user_id', $userId)
                                                ->thisWeek()
                                                ->count();
            
            // Goal progress
            $goals = [
                'weekly_target' => 21, // 3 activities per day * 7 days
                'weekly_actual' => $thisWeekActivities,
                'daily_target' => 3,
                'daily_actual' => $today['summary']['total_activities'],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dashboard summary retrieved successfully',
                'summary' => [
                    'today' => $today,
                    'streaks' => $streaks,
                    'goals' => $goals,
                    'motivational_message' => $this->getMotivationalMessage($streaks['overall']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync existing data (one-time operation)
     */
    public function syncExistingData(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $this->trackerService->syncExistingData($userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Existing data synced successfully to activity tracker',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync existing data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity insights and recommendations
     */
    public function getInsights(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            // Get last 30 days of activity
            $activities = UserActivityLog::where('user_id', $userId)
                                       ->where('activity_date', '>=', now()->subDays(30))
                                       ->get();

            $insights = [
                'most_active_time' => $this->getMostActiveTime($activities),
                'favorite_activities' => $this->getFavoriteActivities($activities),
                'consistency_score' => $this->getConsistencyScore($activities),
                'recommendations' => $this->getRecommendations($activities),
                'mood_analysis' => $this->getMoodAnalysis($activities),
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Activity insights retrieved successfully',
                'insights' => $insights,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get insights: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Private helper methods
    private function getMotivationalMessage(int $streak): string
    {
        return match(true) {
            $streak === 0 => "Mari mulai perjalanan spiritualmu hari ini! 🌟",
            $streak === 1 => "Langkah pertama yang hebat! Lanjutkan besok ya 💪",
            $streak < 7 => "Konsistensi yang bagus! Streak $streak hari 🔥",
            $streak < 30 => "Luar biasa! $streak hari berturut-turut! 🏆",
            default => "Masya Allah! Streak $streak hari - Anda luar biasa! ✨"
        };
    }

    private function getMostActiveTime($activities)
    {
        $hourCounts = [];
        
        foreach ($activities as $activity) {
            $hour = Carbon::parse($activity->activity_time)->format('H');
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        }
        
        if (empty($hourCounts)) return null;
        
        arsort($hourCounts);
        $mostActiveHour = array_key_first($hourCounts);
        
        return [
            'hour' => $mostActiveHour,
            'formatted' => Carbon::createFromTime($mostActiveHour)->format('H:i'),
            'activity_count' => $hourCounts[$mostActiveHour],
        ];
    }

    private function getFavoriteActivities($activities)
    {
        $activityCounts = [];
        
        foreach ($activities as $activity) {
            $type = $activity->activity_type;
            $activityCounts[$type] = ($activityCounts[$type] ?? 0) + 1;
        }
        
        arsort($activityCounts);
        
        return array_slice($activityCounts, 0, 3, true);
    }

    private function getConsistencyScore($activities)
    {
        $totalDays = 30;
        $activeDays = $activities->groupBy('activity_date')->count();
        
        return round(($activeDays / $totalDays) * 100, 1);
    }

    private function getRecommendations($activities)
    {
        $recommendations = [];
        $activityTypes = $activities->pluck('activity_type')->unique()->toArray();
        
        // Recommend missing activity types
        $allTypes = [
            'quran_reading' => 'Baca Al-Quran untuk kedamaian hati',
            'dzikir_session' => 'Dzikir untuk mendekatkan diri pada Allah',
            'breathing_exercise' => 'Latihan pernapasan untuk ketenangan',
            'journal_writing' => 'Tulis jurnal untuk refleksi diri',
        ];
        
        foreach ($allTypes as $type => $message) {
            if (!in_array($type, $activityTypes)) {
                $recommendations[] = $message;
            }
        }
        
        return array_slice($recommendations, 0, 3);
    }

    private function getMoodAnalysis($activities)
    {
        $moods = [];
        
        foreach ($activities as $activity) {
            if ($activity->metadata && isset($activity->metadata['mood_after'])) {
                $moods[] = $activity->metadata['mood_after'];
            }
        }
        
        if (empty($moods)) {
            return ['message' => 'Belum ada data mood yang tercatat'];
        }
        
        $moodCounts = array_count_values($moods);
        arsort($moodCounts);
        
        return [
            'dominant_mood' => array_key_first($moodCounts),
            'mood_distribution' => $moodCounts,
            'total_entries' => count($moods),
        ];
    }
}