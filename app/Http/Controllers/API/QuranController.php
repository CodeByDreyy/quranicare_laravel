<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuranSurah;
use App\Models\QuranAyah;
use Illuminate\Http\JsonResponse;

class QuranController extends Controller
{
    /**
     * Get all surahs
     */
    public function getSurahs(): JsonResponse
    {
        try {
            $surahs = QuranSurah::select([
                'id', 
                'number', 
                'name_arabic', 
                'name_indonesian', 
                'name_english',
                'name_latin', 
                'place', 
                'number_of_ayahs',
                'description_indonesian'
            ])
            ->orderBy('number', 'asc')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Surahs retrieved successfully',
                'data' => $surahs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve surahs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific surah details
     */
    public function getSurah(int $surah): JsonResponse
    {
        try {
            $surahData = QuranSurah::where('number', $surah)
                ->first();

            if (!$surahData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surah not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Surah retrieved successfully',
                'data' => $surahData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve surah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ayahs for specific surah
     */
    public function getAyahs(int $surah): JsonResponse
    {
        try {
            // Get surah info
            $surahData = QuranSurah::where('number', $surah)->first();
            
            if (!$surahData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surah not found'
                ], 404);
            }

            // Get ayahs
            $ayahs = QuranAyah::where('quran_surah_id', $surahData->id)
                ->select([
                    'id',
                    'number',
                    'text_arabic',
                    'text_indonesian',
                    'text_english',
                    'text_latin',
                    'tafsir_indonesian',
                    'audio_url'
                ])
                ->orderBy('number', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Ayahs retrieved successfully',
                'data' => [
                    'surah' => $surahData,
                    'ayahs' => $ayahs
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ayahs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific ayah
     */
    public function getAyah(int $ayah): JsonResponse
    {
        try {
            $ayahData = QuranAyah::with('surah')
                ->find($ayah);

            if (!$ayahData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ayah not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ayah retrieved successfully',
                'data' => $ayahData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ayah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search in Quran
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q');
            $type = $request->get('type', 'translation'); // translation, arabic, latin
            
            if (!$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ], 400);
            }

            $ayahsQuery = QuranAyah::with('surah');

            switch ($type) {
                case 'arabic':
                    $ayahsQuery->where('text_arabic', 'LIKE', "%{$query}%");
                    break;
                case 'latin':
                    $ayahsQuery->where('text_latin', 'LIKE', "%{$query}%");
                    break;
                case 'translation':
                default:
                    $ayahsQuery->where('text_indonesian', 'LIKE', "%{$query}%");
                    break;
            }

            $ayahs = $ayahsQuery->orderBy('quran_surah_id')
                              ->orderBy('number')
                              ->limit(50)
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Search completed successfully',
                'data' => [
                    'query' => $query,
                    'type' => $type,
                    'results' => $ayahs,
                    'total' => $ayahs->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle bookmark for ayah (placeholder - will need bookmark model)
     */
    public function toggleBookmark(Request $request, int $ayah): JsonResponse
    {
        try {
            $user = $request->user();
            $ayahData = QuranAyah::find($ayah);

            if (!$ayahData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ayah not found'
                ], 404);
            }

            // TODO: Implement bookmark functionality when bookmark model is created
            
            return response()->json([
                'success' => true,
                'message' => 'Bookmark functionality will be implemented',
                'data' => [
                    'ayah_id' => $ayah,
                    'user_id' => $user->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle bookmark',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
