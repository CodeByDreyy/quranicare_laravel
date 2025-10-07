<?php

namespace App\Http\Controllers;

use App\Models\DzikirCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DzikirCategoryController extends Controller
{
    /**
     * Get all dzikir categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = DzikirCategory::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dzikir categories retrieved successfully',
                'data' => $categories,
                'total' => $categories->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dzikir categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single dzikir category
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = DzikirCategory::where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Dzikir category retrieved successfully',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dzikir category',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}