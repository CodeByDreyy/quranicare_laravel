<?php

namespace App\Http\Controllers;

use App\Models\BreathingCategory;
use App\Models\BreathingExercise;
use App\Models\BreathingSession;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BreathingExerciseController extends Controller
{
    /**
     * Get all breathing categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = BreathingCategory::active()
                ->with(['exercises' => function($query) {
                    $query->active();
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch breathing categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exercises by category
     */
    public function getExercisesByCategory($categoryId): JsonResponse
    {
        try {
            $exercises = BreathingExercise::where('breathing_category_id', $categoryId)
                ->active()
                ->with('breathingCategory')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $exercises
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exercises',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific exercise details
     */
    public function getExercise($exerciseId): JsonResponse
    {
        try {
            $exercise = BreathingExercise::with('breathingCategory')
                ->findOrFail($exerciseId);

            return response()->json([
                'success' => true,
                'data' => $exercise
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Start a breathing session
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'breathing_exercise_id' => 'required|exists:breathing_exercises,id',
            'planned_duration_minutes' => 'required|integer|min:1|max:60',
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $session = BreathingSession::create([
                'user_id' => $request->user_id,
                'breathing_exercise_id' => $request->breathing_exercise_id,
                'planned_duration_minutes' => $request->planned_duration_minutes,
                'started_at' => now(),
                'completed' => false
            ]);

            $session->load('breathingExercise.breathingCategory');

            // Log activity
            event(new UserActivityEvent(
                $request->user_id,
                'breathing_exercise',
                'Memulai latihan pernapasan: ' . $session->breathingExercise->name,
                [
                    'breathing_session_id' => $session->id,
                    'exercise_name' => $session->breathingExercise->name,
                    'category' => $session->breathingExercise->breathingCategory->name,
                    'planned_duration' => $session->planned_duration_minutes
                ]
            ));

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'Session started successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update session progress
     */
    public function updateSessionProgress(Request $request, $sessionId): JsonResponse
    {
        $request->validate([
            'completed_cycles' => 'required|integer|min:0',
            'actual_duration_seconds' => 'integer|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $session = BreathingSession::findOrFail($sessionId);
            
            $session->update([
                'completed_cycles' => $request->completed_cycles,
                'actual_duration_seconds' => $request->actual_duration_seconds ?? $session->actual_duration_seconds,
                'notes' => $request->notes ?? $session->notes
            ]);

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'Session progress updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a breathing session
     */
    public function completeSession(Request $request, $sessionId): JsonResponse
    {
        $request->validate([
            'completed_cycles' => 'required|integer|min:0',
            'actual_duration_seconds' => 'required|integer|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $session = BreathingSession::findOrFail($sessionId);
            
            $session->update([
                'completed_cycles' => $request->completed_cycles,
                'actual_duration_seconds' => $request->actual_duration_seconds,
                'notes' => $request->notes,
                'completed' => true,
                'completed_at' => now()
            ]);

            $session->load('breathingExercise.breathingCategory');

            // Log completion activity
            event(new UserActivityEvent(
                $session->user_id,
                'breathing_exercise',
                'Menyelesaikan latihan pernapasan: ' . $session->breathingExercise->name,
                [
                    'breathing_session_id' => $session->id,
                    'exercise_name' => $session->breathingExercise->name,
                    'category' => $session->breathingExercise->breathingCategory->name,
                    'completed_cycles' => $session->completed_cycles,
                    'actual_duration' => $session->actual_duration_seconds,
                    'duration' => round($session->actual_duration_seconds / 60, 1)
                ]
            ));

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'Session completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's breathing sessions history
     */
    public function getUserSessions($userId): JsonResponse
    {
        try {
            $sessions = BreathingSession::where('user_id', $userId)
                ->with(['breathingExercise.breathingCategory'])
                ->orderBy('started_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get breathing statistics for user
     */
    public function getUserStats($userId): JsonResponse
    {
        try {
            $stats = [
                'total_sessions' => BreathingSession::where('user_id', $userId)->count(),
                'completed_sessions' => BreathingSession::where('user_id', $userId)->completed()->count(),
                'total_minutes' => BreathingSession::where('user_id', $userId)
                    ->completed()
                    ->sum('actual_duration_seconds') / 60,
                'favorite_exercise' => BreathingSession::where('user_id', $userId)
                    ->selectRaw('breathing_exercise_id, count(*) as session_count')
                    ->groupBy('breathing_exercise_id')
                    ->orderBy('session_count', 'desc')
                    ->with('breathingExercise')
                    ->first()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}