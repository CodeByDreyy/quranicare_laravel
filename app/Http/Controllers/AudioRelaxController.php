<?php

namespace App\Http\Controllers;

use App\Models\AudioRelax;
use App\Models\AudioCategory;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AudioRelaxController extends Controller
{
    /**
     * Get audio by category
     */
    public function getByCategory(int $categoryId): JsonResponse
    {
        try {
            $category = AudioCategory::findOrFail($categoryId);
            
            $audioList = AudioRelax::with('category')
                ->where('audio_category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('play_count', 'desc')
                ->orderBy('rating', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Audio by category retrieved successfully',
                'data' => $audioList,
                'category' => $category,
                'total' => $audioList->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audio by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single audio detail
     */
    public function show(int $id): JsonResponse
    {
        try {
            $audio = AudioRelax::with('category')
                ->where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Audio detail retrieved successfully',
                'data' => $audio
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audio detail',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get popular audio
     */
    public function popular(): JsonResponse
    {
        try {
            $audioList = AudioRelax::with('category')
                ->where('is_active', true)
                ->orderBy('play_count', 'desc')
                ->orderBy('rating', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Popular audio retrieved successfully',
                'data' => $audioList,
                'total' => $audioList->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve popular audio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search audio
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ], 400);
            }

            $audioList = AudioRelax::with('category')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('artist', 'LIKE', "%{$query}%");
                })
                ->orderBy('play_count', 'desc')
                ->orderBy('rating', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Audio search results retrieved successfully',
                'data' => $audioList,
                'query' => $query,
                'total' => $audioList->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search audio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update play count and log activity
     */
    public function updatePlayCount(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'session_duration' => 'nullable|integer|min:0'
        ]);

        try {
            $audio = AudioRelax::findOrFail($id);
            $audio->increment('play_count');
            $audio->load('category');

            // Log listening activity
            event(new UserActivityEvent(
                $request->user_id,
                'audio_listening',
                'Mendengarkan: ' . $audio->title,
                [
                    'audio_id' => $audio->id,
                    'audio_title' => $audio->title,
                    'artist' => $audio->artist,
                    'category' => $audio->category->name,
                    'session_duration' => $request->session_duration,
                    'duration' => $request->session_duration ? round($request->session_duration / 60, 1) : null
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Play count updated successfully',
                'data' => [
                    'play_count' => $audio->play_count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update play count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rate audio
     */
    public function rateAudio(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'rating' => 'required|numeric|min:1|max:5'
            ]);

            $audio = AudioRelax::findOrFail($id);
            
            // Calculate new average rating
            $newRatingSum = ($audio->rating * $audio->rating_count) + $request->rating;
            $newRatingCount = $audio->rating_count + 1;
            $newAverageRating = $newRatingSum / $newRatingCount;

            $audio->update([
                'rating' => round($newAverageRating, 2),
                'rating_count' => $newRatingCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audio rated successfully',
                'data' => [
                    'rating' => $audio->rating,
                    'rating_count' => $audio->rating_count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rate audio',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}