<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioRelax;
use App\Models\AudioCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminAudioRelaxController extends Controller
{
    // AUDIO RELAX CRUD
    public function index(Request $request)
    {
        try {
            $query = AudioRelax::with('category');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('artist', 'like', "%{$search}%");
                });
            }

            if ($request->has('category_id')) {
                $query->where('audio_category_id', $request->category_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('is_premium')) {
                $query->where('is_premium', $request->is_premium);
            }

            $audioRelax = $query->orderBy('created_at', 'desc')
                               ->paginate($request->get('per_page', 15));

            return response()->json([
                'message' => 'Audio Relax retrieved successfully',
                'data' => $audioRelax
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve audio relax',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audio_category_id' => 'required|exists:audio_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'artist' => 'nullable|string|max:255',
            'duration_seconds' => 'required|integer|min:1',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg|max:51200', // 50MB max
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'audio_path' => 'required_without:audio_file|string', // URL untuk YouTube
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except(['audio_file', 'thumbnail_file']);

            // Handle audio file upload or URL
            if ($request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store('audio_relax', 'public');
                $data['audio_path'] = $audioPath;
            } elseif ($request->filled('audio_path')) {
                $data['audio_path'] = $request->audio_path;
            }

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_file')) {
                $thumbnailPath = $request->file('thumbnail_file')->store('audio_thumbnails', 'public');
                $data['thumbnail_path'] = $thumbnailPath;
            }

            $audioRelax = AudioRelax::create($data);

            return response()->json([
                'message' => 'Audio Relax created successfully',
                'data' => $audioRelax->load('category')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create audio relax',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $audioRelax = AudioRelax::with('category')->findOrFail($id);

            return response()->json([
                'message' => 'Audio Relax retrieved successfully',
                'data' => $audioRelax
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Audio Relax not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'audio_category_id' => 'required|exists:audio_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'artist' => 'nullable|string|max:255',
            'duration_seconds' => 'required|integer|min:1',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg|max:51200',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'audio_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $audioRelax = AudioRelax::findOrFail($id);
            $data = $request->except(['audio_file', 'thumbnail_file']);

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file if it's a local file
                if ($audioRelax->audio_path && !filter_var($audioRelax->audio_path, FILTER_VALIDATE_URL)) {
                    Storage::disk('public')->delete($audioRelax->audio_path);
                }
                $audioPath = $request->file('audio_file')->store('audio_relax', 'public');
                $data['audio_path'] = $audioPath;
            } elseif ($request->filled('audio_path')) {
                $data['audio_path'] = $request->audio_path;
            }

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_file')) {
                // Delete old thumbnail
                if ($audioRelax->thumbnail_path) {
                    Storage::disk('public')->delete($audioRelax->thumbnail_path);
                }
                $thumbnailPath = $request->file('thumbnail_file')->store('audio_thumbnails', 'public');
                $data['thumbnail_path'] = $thumbnailPath;
            }

            $audioRelax->update($data);

            return response()->json([
                'message' => 'Audio Relax updated successfully',
                'data' => $audioRelax->load('category')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update audio relax',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $audioRelax = AudioRelax::findOrFail($id);

            // Delete files if they are local files
            if ($audioRelax->audio_path && !filter_var($audioRelax->audio_path, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($audioRelax->audio_path);
            }
            if ($audioRelax->thumbnail_path) {
                Storage::disk('public')->delete($audioRelax->thumbnail_path);
            }

            $audioRelax->delete();

            return response()->json([
                'message' => 'Audio Relax deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete audio relax',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // CATEGORIES CRUD
    public function getCategories()
    {
        try {
            $categories = AudioCategory::orderBy('name')->get();

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
            'name' => 'required|string|max:255|unique:audio_categories,name',
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
            $category = AudioCategory::create($request->all());

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
            'name' => 'required|string|max:255|unique:audio_categories,name,' . $id,
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
            $category = AudioCategory::findOrFail($id);
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
            $category = AudioCategory::findOrFail($id);

            // Check if category has audio relax
            if ($category->audioRelax()->count() > 0) {
                return response()->json([
                    'error' => 'Cannot delete category that contains audio relax'
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
