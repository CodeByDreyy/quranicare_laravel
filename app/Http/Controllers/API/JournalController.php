<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\QuranAyah;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JournalController extends Controller
{
    /**
     * Get user's journal entries
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 10);
            $tag = $request->get('tag');
            $mood = $request->get('mood');
            $favorite = $request->get('favorite');

            $query = Journal::where('user_id', $user->id)
                           ->with(['ayah.surah'])
                           ->orderBy('created_at', 'desc');

            if ($tag) {
                $query->byTag($tag);
            }

            if ($mood) {
                $query->byMood($mood);
            }

            if ($favorite === 'true') {
                $query->favorites();
            }

            $journals = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Journals retrieved successfully',
                'data' => $journals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new journal entry
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'quran_ayah_id' => 'required|exists:quran_ayahs,id',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'mood_after' => 'nullable|string|in:senang,sedih,biasa_saja,marah,murung,tenang,bersyukur',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'journal_date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            $journal = Journal::create([
                'user_id' => $user->id,
                'quran_ayah_id' => $request->quran_ayah_id,
                'title' => $request->title,
                'content' => $request->content,
                'mood_after' => $request->mood_after,
                'tags' => $request->tags ?? [],
                'journal_date' => $request->journal_date ?? now()->toDateString(),
                'is_private' => true,
                'is_favorite' => false
            ]);

            $journal->load(['ayah.surah']);

            // Log journal activity
            event(new UserActivityEvent(
                $user->id,
                'journal_entry',
                'Menulis jurnal: ' . $journal->title,
                [
                    'journal_id' => $journal->id,
                    'title' => $journal->title,
                    'word_count' => str_word_count(strip_tags($journal->content)),
                    'mood_after' => $journal->mood_after,
                    'tags' => $journal->tags,
                    'ayah_reference' => $journal->ayah ? $journal->ayah->surah->name_latin . ':' . $journal->ayah->verse_number : null
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Journal created successfully',
                'data' => $journal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific journal entry
     */
    public function show(Request $request, Journal $journal): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if journal belongs to user
            if ($journal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to journal'
                ], 403);
            }

            $journal->load(['ayah.surah']);

            return response()->json([
                'success' => true,
                'message' => 'Journal retrieved successfully',
                'data' => $journal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update journal entry
     */
    public function update(Request $request, Journal $journal): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if journal belongs to user
            if ($journal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to journal'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'mood_after' => 'nullable|string|in:senang,sedih,biasa_saja,marah,murung,tenang,bersyukur',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'journal_date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $journal->update($request->only([
                'title', 'content', 'mood_after', 'tags', 'journal_date'
            ]));

            $journal->load(['ayah.surah']);

            return response()->json([
                'success' => true,
                'message' => 'Journal updated successfully',
                'data' => $journal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete journal entry
     */
    public function destroy(Request $request, Journal $journal): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if journal belongs to user
            if ($journal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to journal'
                ], 403);
            }

            $journal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Journal deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete journal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(Request $request, Journal $journal): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if journal belongs to user
            if ($journal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to journal'
                ], 403);
            }

            $journal->update([
                'is_favorite' => !$journal->is_favorite
            ]);

            return response()->json([
                'success' => true,
                'message' => $journal->is_favorite ? 'Added to favorites' : 'Removed from favorites',
                'data' => [
                    'is_favorite' => $journal->is_favorite
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get journals for specific ayah
     */
    public function getByAyah(Request $request, int $ayahId): JsonResponse
    {
        try {
            $user = $request->user();

            $ayah = QuranAyah::find($ayahId);
            if (!$ayah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ayah not found'
                ], 404);
            }

            $journals = Journal::where('user_id', $user->id)
                              ->where('quran_ayah_id', $ayahId)
                              ->with(['ayah.surah'])
                              ->orderBy('created_at', 'desc')
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Ayah journals retrieved successfully',
                'data' => [
                    'ayah' => $ayah->load('surah'),
                    'journals' => $journals
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ayah journals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tag suggestions
     */
    public function getTagSuggestions(Request $request): JsonResponse
    {
        try {
            // For public route testing, we'll use a dummy user ID of 1
            // In production, this should be removed and only protected routes should be used
            $userId = $request->user() ? $request->user()->id : 1;

            $tags = Journal::where('user_id', $userId)
                          ->whereNotNull('tags')
                          ->pluck('tags')
                          ->flatten()
                          ->unique()
                          ->values();

            $defaultTags = [
                'refleksi', 'doa', 'syukur', 'muhasabah', 'ibrah',
                'tafakkur', 'dzikir', 'tadabbur', 'hikmah', 'nasihat'
            ];

            $allTags = $tags->merge($defaultTags)->unique()->values();

            return response()->json([
                'success' => true,
                'message' => 'Tag suggestions retrieved successfully',
                'data' => $allTags
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tag suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific ayah with reflections
     */
    public function getAyahReflections(Request $request, int $ayah): JsonResponse
    {
        try {
            // For public route testing, we'll use a dummy user ID of 1
            // In production, this should be removed and only protected routes should be used
            $userId = $request->user() ? $request->user()->id : 1;
            
            $ayahData = QuranAyah::find($ayah);
            if (!$ayahData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ayah not found'
                ], 404);
            }

            $reflections = Journal::where('user_id', $userId)
                                ->where('quran_ayah_id', $ayah)
                                ->with(['ayah.surah'])
                                ->orderBy('created_at', 'desc')
                                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Ayah reflections retrieved successfully',
                'data' => [
                    'ayah' => $ayahData->load('surah'),
                    'reflections' => $reflections,
                    'reflection_count' => $reflections->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ayah reflections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create reflection for specific ayah
     */
    public function createAyahReflection(Request $request, int $ayah): JsonResponse
    {
        try {
            $ayahData = QuranAyah::find($ayah);
            if (!$ayahData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ayah not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string|min:10',
                'mood_after' => 'nullable|string|in:senang,sedih,biasa_saja,marah,murung,tenang,bersyukur',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // For public route testing, we'll use a dummy user ID of 1
            // In production, this should be removed and only protected routes should be used
            $userId = $request->user() ? $request->user()->id : 1;

            $journal = Journal::create([
                'user_id' => $userId,
                'quran_ayah_id' => $ayah,
                'title' => $request->title,
                'content' => $request->content,
                'mood_after' => $request->mood_after,
                'tags' => $request->tags ?? [],
                'journal_date' => now()->toDateString(),
                'is_private' => true,
                'is_favorite' => false
            ]);

            $journal->load(['ayah.surah']);

            // Log reflection activity
            event(new UserActivityEvent(
                $userId,
                'journal_entry',
                'Menulis refleksi: ' . $journal->title,
                [
                    'journal_id' => $journal->id,
                    'title' => $journal->title,
                    'word_count' => str_word_count(strip_tags($journal->content)),
                    'mood_after' => $journal->mood_after,
                    'tags' => $journal->tags,
                    'ayah_reference' => $journal->ayah->surah->name_latin . ':' . $journal->ayah->verse_number,
                    'reflection_type' => 'ayah_reflection'
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Ayah reflection created successfully',
                'data' => $journal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ayah reflection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent reflections
     */
    public function getRecentReflections(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $days = $request->get('days', 7);
            $limit = $request->get('limit', 10);

            $reflections = Journal::where('user_id', $user->id)
                                ->recent($days)
                                ->with(['ayah.surah'])
                                ->orderBy('created_at', 'desc')
                                ->limit($limit)
                                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Recent reflections retrieved successfully',
                'data' => [
                    'reflections' => $reflections,
                    'period' => "{$days} days",
                    'count' => $reflections->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent reflections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reflection statistics
     */
    public function getReflectionStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $stats = [
                'total_reflections' => Journal::where('user_id', $user->id)->count(),
                'this_month' => Journal::where('user_id', $user->id)
                                    ->whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count(),
                'this_week' => Journal::where('user_id', $user->id)->recent(7)->count(),
                'favorites' => Journal::where('user_id', $user->id)->favorites()->count(),
                'with_ayah' => Journal::where('user_id', $user->id)
                                   ->whereNotNull('quran_ayah_id')
                                   ->count(),
                'mood_distribution' => Journal::where('user_id', $user->id)
                                           ->whereNotNull('mood_after')
                                           ->selectRaw('mood_after, COUNT(*) as count')
                                           ->groupBy('mood_after')
                                           ->pluck('count', 'mood_after'),
                'most_used_tags' => Journal::where('user_id', $user->id)
                                        ->whereNotNull('tags')
                                        ->pluck('tags')
                                        ->flatten()
                                        ->countBy()
                                        ->sortDesc()
                                        ->take(10),
                'reflection_streak' => $this->calculateReflectionStreak($user->id)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Reflection statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reflection statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate reflection streak (days with reflections)
     */
    private function calculateReflectionStreak(int $userId): int
    {
        $streak = 0;
        $currentDate = now()->toDateString();
        
        while (true) {
            $hasReflection = Journal::where('user_id', $userId)
                                  ->whereDate('created_at', $currentDate)
                                  ->exists();
            
            if (!$hasReflection) {
                break;
            }
            
            $streak++;
            $currentDate = now()->subDays($streak)->toDateString();
        }
        
        return $streak;
    }
}
