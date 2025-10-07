<?php

namespace App\Http\Controllers;

use App\Models\DzikirDoa;
use App\Models\DzikirCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DzikirDoaController extends Controller
{
    /**
     * Get all dzikir doa
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DzikirDoa::with('category')
                ->where('is_active', true)
                ->orderBy('title');

            // Filter by category if provided
            if ($request->has('category_id')) {
                $query->where('dzikir_category_id', $request->category_id);
            }

            // Filter featured if provided
            if ($request->has('featured')) {
                $query->where('is_featured', true);
            }

            $dzikirDoa = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Dzikir doa retrieved successfully',
                'data' => $dzikirDoa,
                'total' => $dzikirDoa->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dzikir doa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dzikir doa by category
     */
    public function getByCategory(int $categoryId): JsonResponse
    {
        try {
            $category = DzikirCategory::findOrFail($categoryId);
            
            $dzikirDoa = DzikirDoa::with('category')
                ->where('dzikir_category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dzikir doa by category retrieved successfully',
                'data' => $dzikirDoa,
                'category' => $category,
                'total' => $dzikirDoa->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dzikir doa by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single dzikir doa
     */
    public function show(int $id): JsonResponse
    {
        try {
            $dzikirDoa = DzikirDoa::with('category')
                ->where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Dzikir doa detail retrieved successfully',
                'data' => $dzikirDoa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dzikir doa detail',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get featured dzikir doa
     */
    public function featured(): JsonResponse
    {
        try {
            $dzikirDoa = DzikirDoa::with('category')
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Featured dzikir doa retrieved successfully',
                'data' => $dzikirDoa,
                'total' => $dzikirDoa->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured dzikir doa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search dzikir doa
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

            $dzikirDoa = DzikirDoa::with('category')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('arabic_text', 'LIKE', "%{$query}%")
                      ->orWhere('latin_text', 'LIKE', "%{$query}%")
                      ->orWhere('indonesian_translation', 'LIKE', "%{$query}%")
                      ->orWhere('benefits', 'LIKE', "%{$query}%")
                      ->orWhere('context', 'LIKE', "%{$query}%");
                })
                ->orderBy('title')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dzikir doa search results retrieved successfully',
                'data' => $dzikirDoa,
                'query' => $query,
                'total' => $dzikirDoa->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search dzikir doa',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}