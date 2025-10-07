<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Mood;
use App\Models\MoodStatistic;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MoodController extends Controller
{
    /**
     * Get user's mood entries
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $moods = Mood::where('user_id', $user->id)
            ->when($request->date, function ($query, $date) {
                return $query->where('mood_date', $date);
            })
            ->when($request->mood_type, function ($query, $moodType) {
                return $query->where('mood_type', $moodType);
            })
            ->orderBy('mood_date', 'desc')
            ->orderBy('mood_time', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $moods
        ]);
    }

    /**
     * Store a new mood entry
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mood_type' => 'required|in:senang,sedih,biasa_saja,marah,murung',
            'notes' => 'nullable|string|max:500',
            'mood_date' => 'nullable|date',
            'mood_time' => 'nullable|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $today = Carbon::now()->toDateString();
        $now = Carbon::now()->toTimeString();

        // Check if user has already selected mood today
        $existingMoodToday = Mood::where('user_id', $user->id)
            ->where('mood_date', $today)
            ->exists();

        if ($existingMoodToday) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memilih mood hari ini',
                'error_code' => 'MOOD_ALREADY_SELECTED_TODAY'
            ], 422);
        }

        $mood = Mood::create([
            'user_id' => $user->id,
            'mood_type' => $request->mood_type,
            'notes' => $request->notes,
            'mood_date' => $request->mood_date ?? $today,
            'mood_time' => $request->mood_time ?? $now
        ]);

        // Update daily mood statistics
        $this->updateMoodStatistics($user->id, $request->mood_date ?? $today);

        // Log mood tracking activity
        event(new UserActivityEvent(
            $user->id,
            'mood_tracking',
            'Mencatat mood: ' . ucfirst($mood->mood_type),
            [
                'mood_id' => $mood->id,
                'mood_type' => $mood->mood_type,
                'mood_level' => $this->getMoodLevel($mood->mood_type),
                'has_notes' => !empty($mood->notes),
                'mood_time' => $mood->mood_time
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Mood recorded successfully',
            'data' => $mood
        ], 201);
    }

    /**
     * Get today's mood entries
     */
    public function getTodayMoods(): JsonResponse
    {
        $user = Auth::user();
        $today = Carbon::now()->toDateString();

        $moods = Mood::where('user_id', $user->id)
            ->where('mood_date', $today)
            ->orderBy('mood_time', 'desc')
            ->get();

        $moodCounts = $moods->countBy('mood_type')->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'moods' => $moods,
                'counts' => $moodCounts,
                'total_entries' => $moods->count()
            ]
        ]);
    }

    /**
     * Get mood statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->period ?? 'week'; // week, month, year

        $startDate = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfWeek()
        };

        $statistics = MoodStatistic::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->orderBy('date', 'desc')
            ->get();

        // Calculate overall statistics
        $totalEntries = $statistics->sum('total_entries');
        $moodCounts = [];
        foreach (['senang', 'sedih', 'biasa_saja', 'marah', 'murung'] as $mood) {
            $moodCounts[$mood] = $statistics->sum(function ($stat) use ($mood) {
                return $stat->mood_counts[$mood] ?? 0;
            });
        }

        $averageMoodScore = $statistics->where('mood_score', '>', 0)->avg('mood_score');

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'summary' => [
                    'total_entries' => $totalEntries,
                    'mood_counts' => $moodCounts,
                    'average_mood_score' => round($averageMoodScore, 2),
                    'period' => $period
                ]
            ]
        ]);
    }

    /**
     * Get mood history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->limit ?? 30;

        $history = Mood::where('user_id', $user->id)
            ->orderBy('mood_date', 'desc')
            ->orderBy('mood_time', 'desc')
            ->limit($limit)
            ->get()
            ->groupBy('mood_date');

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Update mood entry
     */
    public function update(Request $request, Mood $mood): JsonResponse
    {
        if ($mood->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'mood_type' => 'required|in:senang,sedih,biasa_saja,marah,murung',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $mood->update($request->only(['mood_type', 'notes']));

        // Update daily mood statistics
        $this->updateMoodStatistics($mood->user_id, $mood->mood_date);

        return response()->json([
            'success' => true,
            'message' => 'Mood updated successfully',
            'data' => $mood
        ]);
    }

    /**
     * Delete mood entry
     */
    public function destroy(Mood $mood): JsonResponse
    {
        if ($mood->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $mood->delete();

        // Update daily mood statistics
        $this->updateMoodStatistics($mood->user_id, $mood->mood_date);

        return response()->json([
            'success' => true,
            'message' => 'Mood deleted successfully'
        ]);
    }

    /**
     * Get mood level for activity tracking
     */
    private function getMoodLevel($moodType): int
    {
        return match($moodType) {
            'senang' => 5,
            'biasa_saja' => 3,
            'sedih' => 2,
            'murung' => 1,
            'marah' => 1,
            default => 3
        };
    }

    /**
     * Update daily mood statistics
     */
    private function updateMoodStatistics($userId, $date)
    {
        $moods = Mood::where('user_id', $userId)
            ->where('mood_date', $date)
            ->get();

        $moodCounts = $moods->countBy('mood_type')->toArray();
        
        // Fill missing mood types with 0
        foreach (['senang', 'sedih', 'biasa_saja', 'marah', 'murung'] as $moodType) {
            if (!isset($moodCounts[$moodType])) {
                $moodCounts[$moodType] = 0;
            }
        }

        $dominantMood = collect($moodCounts)->sortDesc()->keys()->first();
        
        // Calculate mood score (simple algorithm: positive moods = higher score)
        $moodScore = 0;
        if ($moods->count() > 0) {
            $scores = [
                'senang' => 5,
                'biasa_saja' => 3,
                'sedih' => 2,
                'murung' => 1,
                'marah' => 1
            ];
            
            $totalScore = 0;
            foreach ($moodCounts as $mood => $count) {
                $totalScore += $scores[$mood] * $count;
            }
            $moodScore = $totalScore / $moods->count();
        }

        MoodStatistic::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'mood_counts' => $moodCounts,
                'dominant_mood' => $dominantMood,
                'mood_score' => $moodScore,
                'total_entries' => $moods->count()
            ]
        );
    }
}
