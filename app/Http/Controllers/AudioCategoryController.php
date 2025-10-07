<?php

namespace App\Http\Controllers;

use App\Models\AudioCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AudioCategoryController extends Controller
{
    /**
     * Get all audio categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = AudioCategory::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Audio categories retrieved successfully',
                'data' => $categories,
                'total' => $categories->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audio categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single audio category
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = AudioCategory::where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Audio category retrieved successfully',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audio category',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}