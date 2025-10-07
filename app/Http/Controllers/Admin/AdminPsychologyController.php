<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PsychologyMaterial;
use App\Models\PsychologyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminPsychologyController extends Controller
{
    // PSYCHOLOGY MATERIALS CRUD
    public function index(Request $request)
    {
        try {
            $query = PsychologyMaterial::with('category');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('summary', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%");
                });
            }

            if ($request->has('category_id')) {
                $query->where('psychology_category_id', $request->category_id);
            }

            if ($request->has('difficulty_level')) {
                $query->where('difficulty_level', $request->difficulty_level);
            }

            if ($request->has('is_published')) {
                $query->where('is_published', $request->is_published);
            }

            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->is_featured);
            }

            $materials = $query->orderBy('created_at', 'desc')
                              ->paginate($request->get('per_page', 15));

            return response()->json([
                'message' => 'Psychology Materials retrieved successfully',
                'data' => $materials
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve psychology materials',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'psychology_category_id' => 'required|exists:psychology_categories,id',
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'author' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_read_time' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except('featured_image_file');
            $data['tags'] = json_encode($request->tags);

            // Handle featured image upload
            if ($request->hasFile('featured_image_file')) {
                $imagePath = $request->file('featured_image_file')->store('psychology_images', 'public');
                $data['featured_image'] = $imagePath;
            }

            // Set published_at if is_published is true
            if ($request->is_published && !$request->published_at) {
                $data['published_at'] = now();
            }

            $material = PsychologyMaterial::create($data);

            return response()->json([
                'message' => 'Psychology Material created successfully',
                'data' => $material->load('category')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create psychology material',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $material = PsychologyMaterial::with('category')->findOrFail($id);

            return response()->json([
                'message' => 'Psychology Material retrieved successfully',
                'data' => $material
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Psychology Material not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'psychology_category_id' => 'required|exists:psychology_categories,id',
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'author' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_read_time' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $material = PsychologyMaterial::findOrFail($id);
            $data = $request->except('featured_image_file');
            $data['tags'] = json_encode($request->tags);

            // Handle featured image upload
            if ($request->hasFile('featured_image_file')) {
                // Delete old image
                if ($material->featured_image) {
                    Storage::disk('public')->delete($material->featured_image);
                }
                $imagePath = $request->file('featured_image_file')->store('psychology_images', 'public');
                $data['featured_image'] = $imagePath;
            }

            // Handle publishing
            if ($request->is_published && !$material->published_at && !$request->published_at) {
                $data['published_at'] = now();
            } elseif (!$request->is_published) {
                $data['published_at'] = null;
            }

            $material->update($data);

            return response()->json([
                'message' => 'Psychology Material updated successfully',
                'data' => $material->load('category')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update psychology material',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $material = PsychologyMaterial::findOrFail($id);

            // Delete featured image if exists
            if ($material->featured_image) {
                Storage::disk('public')->delete($material->featured_image);
            }

            $material->delete();

            return response()->json([
                'message' => 'Psychology Material deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete psychology material',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // CATEGORIES CRUD
    public function getCategories()
    {
        try {
            $categories = PsychologyCategory::orderBy('sort_order')->orderBy('name')->get();

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
            'name' => 'required|string|max:255|unique:psychology_categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            // Set sort_order if not provided
            if (!$request->has('sort_order')) {
                $data['sort_order'] = PsychologyCategory::max('sort_order') + 1;
            }

            $category = PsychologyCategory::create($data);

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
            'name' => 'required|string|max:255|unique:psychology_categories,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $category = PsychologyCategory::findOrFail($id);
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
            $category = PsychologyCategory::findOrFail($id);

            // Check if category has materials
            if ($category->materials()->count() > 0) {
                return response()->json([
                    'error' => 'Cannot delete category that contains materials'
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
