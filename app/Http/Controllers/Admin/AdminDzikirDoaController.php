<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DzikirDoa;
use App\Models\DzikirCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminDzikirDoaController extends Controller
{
    // DZIKIR DOA CRUD
    public function index(Request $request)
    {
        try {
            $query = DzikirDoa::with('category');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('arabic_text', 'like', "%{$search}%")
                      ->orWhere('indonesian_translation', 'like', "%{$search}%");
                });
            }

            if ($request->has('category_id')) {
                $query->where('dzikir_category_id', $request->category_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $dzikirDoa = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'message' => 'Dzikir Doa retrieved successfully',
                'data' => $dzikirDoa
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve dzikir doa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dzikir_category_id' => 'required|exists:dzikir_categories,id',
            'title' => 'required|string|max:255',
            'arabic_text' => 'required|string',
            'latin_text' => 'nullable|string',
            'indonesian_translation' => 'required|string',
            'benefits' => 'nullable|string',
            'context' => 'nullable|string',
            'source' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:1',
            'emotional_tags' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except('audio_file');
            $data['emotional_tags'] = json_encode($request->emotional_tags);

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store('dzikir_audio', 'public');
                $data['audio_path'] = $audioPath;
            }

            $dzikirDoa = DzikirDoa::create($data);

            return response()->json([
                'message' => 'Dzikir Doa created successfully',
                'data' => $dzikirDoa->load('category')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create dzikir doa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $dzikirDoa = DzikirDoa::with('category')->findOrFail($id);

            return response()->json([
                'message' => 'Dzikir Doa retrieved successfully',
                'data' => $dzikirDoa
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Dzikir Doa not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'dzikir_category_id' => 'required|exists:dzikir_categories,id',
            'title' => 'required|string|max:255',
            'arabic_text' => 'required|string',
            'latin_text' => 'nullable|string',
            'indonesian_translation' => 'required|string',
            'benefits' => 'nullable|string',
            'context' => 'nullable|string',
            'source' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:1',
            'emotional_tags' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $dzikirDoa = DzikirDoa::findOrFail($id);
            $data = $request->except('audio_file');
            $data['emotional_tags'] = json_encode($request->emotional_tags);

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file
                if ($dzikirDoa->audio_path) {
                    Storage::disk('public')->delete($dzikirDoa->audio_path);
                }
                $audioPath = $request->file('audio_file')->store('dzikir_audio', 'public');
                $data['audio_path'] = $audioPath;
            }

            $dzikirDoa->update($data);

            return response()->json([
                'message' => 'Dzikir Doa updated successfully',
                'data' => $dzikirDoa->load('category')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update dzikir doa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $dzikirDoa = DzikirDoa::findOrFail($id);

            // Delete audio file if exists
            if ($dzikirDoa->audio_path) {
                Storage::disk('public')->delete($dzikirDoa->audio_path);
            }

            $dzikirDoa->delete();

            return response()->json([
                'message' => 'Dzikir Doa deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete dzikir doa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // CATEGORIES CRUD
    public function getCategories()
    {
        try {
            $categories = DzikirCategory::orderBy('name')->get();

            return response()->json([
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dzikir_categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $category = DzikirCategory::create($request->all());

            return response()->json([
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create category',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dzikir_categories,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $category = DzikirCategory::findOrFail($id);
            $category->update($request->all());

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update category',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyCategory($id)
    {
        try {
            $category = DzikirCategory::findOrFail($id);

            // Check if category has dzikir doa
            if ($category->dzikirDoa()->count() > 0) {
                return response()->json([
                    'error' => 'Cannot delete category that contains dzikir doa'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete category',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
