<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoaDzikir;
use App\Models\AudioRelax;
use App\Models\PsychologyMaterial;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Get Admin Dashboard Statistics
     */
    public function getDashboardStats()
    {
        try {
            // User Statistics
            $totalUsers = User::count();
            $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count();
            $newUsersThisMonth = User::whereMonth('created_at', Carbon::now()->month)->count();
            
            // Content Statistics
            $totalDzikirDoa = DoaDzikir::where('is_active', true)->count();
            $totalAudioRelax = AudioRelax::where('is_active', true)->count();
            $totalPsychology = PsychologyMaterial::where('is_published', true)->count();
            $totalNotifications = Notification::count();
            
            // Featured Content
            $featuredDzikirDoa = DoaDzikir::where('is_featured', true)->where('is_active', true)->count();
            $premiumAudio = AudioRelax::where('is_premium', true)->where('is_active', true)->count();
            $featuredPsychology = PsychologyMaterial::where('is_featured', true)->where('is_published', true)->count();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'user_stats' => [
                        'total_users' => $totalUsers,
                        'active_users' => $activeUsers,
                        'new_users_this_month' => $newUsersThisMonth,
                    ],
                    'content_stats' => [
                        'total_dzikir_doa' => $totalDzikirDoa,
                        'total_audio_relax' => $totalAudioRelax,
                        'total_psychology' => $totalPsychology,
                        'total_notifications' => $totalNotifications,
                        'featured_dzikir_doa' => $featuredDzikirDoa,
                        'premium_audio' => $premiumAudio,
                        'featured_psychology' => $featuredPsychology,
                    ],
                    'recent_activity' => [
                        'recent_users' => User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']),
                        'recent_content' => [
                            'dzikir_doa' => DoaDzikir::latest()->limit(3)->get(['id', 'nama', 'grup', 'created_at']),
                            'audio_relax' => AudioRelax::latest()->limit(3)->get(['id', 'title', 'artist', 'created_at']),
                            'psychology' => PsychologyMaterial::latest()->limit(3)->get(['id', 'title', 'author', 'created_at']),
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get dashboard stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Dzikir Doa List with Pagination
     */
    public function getDzikirDoaList(Request $request)
    {
        try {
            $query = DoaDzikir::query();
            
            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('grup', 'like', "%{$search}%")
                      ->orWhere('idn', 'like', "%{$search}%");
                });
            }
            
            // Filter by group
            if ($request->has('grup') && !empty($request->grup)) {
                $query->where('grup', $request->grup);
            }
            
            // Filter by status
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool)$request->is_active);
            }
            
            // Filter by featured
            if ($request->has('is_featured') && $request->is_featured !== '') {
                $query->where('is_featured', (bool)$request->is_featured);
            }
            
            $dzikirDoa = $query->orderBy('created_at', 'desc')
                              ->paginate($request->get('per_page', 15));
            
            return response()->json([
                'status' => 'success',
                'data' => $dzikirDoa
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get dzikir doa list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Audio Relax List with Pagination
     */
    public function getAudioRelaxList(Request $request)
    {
        try {
            $query = AudioRelax::with('category');
            
            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('artist', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Filter by category
            if ($request->has('audio_category_id') && !empty($request->audio_category_id)) {
                $query->where('audio_category_id', $request->audio_category_id);
            }
            
            // Filter by status
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool)$request->is_active);
            }
            
            // Filter by premium
            if ($request->has('is_premium') && $request->is_premium !== '') {
                $query->where('is_premium', (bool)$request->is_premium);
            }
            
            $audioRelax = $query->orderBy('created_at', 'desc')
                               ->paginate($request->get('per_page', 15));
            
            return response()->json([
                'status' => 'success',
                'data' => $audioRelax
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get audio relax list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Psychology Materials List with Pagination
     */
    public function getPsychologyMaterialsList(Request $request)
    {
        try {
            $query = PsychologyMaterial::with('category');
            
            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('summary', 'like', "%{$search}%");
                });
            }
            
            // Filter by category
            if ($request->has('psychology_category_id') && !empty($request->psychology_category_id)) {
                $query->where('psychology_category_id', $request->psychology_category_id);
            }
            
            // Filter by published status
            if ($request->has('is_published') && $request->is_published !== '') {
                $query->where('is_published', (bool)$request->is_published);
            }
            
            // Filter by featured
            if ($request->has('is_featured') && $request->is_featured !== '') {
                $query->where('is_featured', (bool)$request->is_featured);
            }
            
            // Filter by difficulty
            if ($request->has('difficulty_level') && !empty($request->difficulty_level)) {
                $query->where('difficulty_level', $request->difficulty_level);
            }
            
            $psychology = $query->orderBy('created_at', 'desc')
                               ->paginate($request->get('per_page', 15));
            
            return response()->json([
                'status' => 'success',
                'data' => $psychology
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get psychology materials list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Notifications List with Pagination
     */
    public function getNotificationsList(Request $request)
    {
        try {
            $query = Notification::with('user:id,name,email');
            
            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }
            
            // Filter by type
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }
            
            // Filter by read status
            if ($request->has('is_read') && $request->is_read !== '') {
                $query->where('is_read', (bool)$request->is_read);
            }
            
            $notifications = $query->orderBy('created_at', 'desc')
                                  ->paginate($request->get('per_page', 15));
            
            return response()->json([
                'status' => 'success',
                'data' => $notifications
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get notifications list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new Dzikir Doa
     */
    public function createDzikirDoa(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'grup' => 'required|string|max:100',
                'arab' => 'required|string',
                'idn' => 'required|string',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
            ]);

            $dzikirDoa = DoaDzikir::create([
                'nama' => $validated['nama'],
                'grup' => $validated['grup'],
                'arab' => $validated['arab'],
                'idn' => $validated['idn'],
                'is_active' => $validated['is_active'] ?? true,
                'is_featured' => $validated['is_featured'] ?? false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dzikir Doa created successfully',
                'data' => $dzikirDoa
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create dzikir doa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Dzikir Doa Detail
     */
    public function getDzikirDoaDetail($id)
    {
        try {
            $dzikirDoa = DoaDzikir::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $dzikirDoa
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get dzikir doa detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Dzikir Doa
     */
    public function updateDzikirDoa(Request $request, $id)
    {
        try {
            $dzikirDoa = DoaDzikir::findOrFail($id);
            
            $validated = $request->validate([
                'nama' => 'sometimes|required|string|max:255',
                'grup' => 'sometimes|required|string|max:100',
                'arab' => 'sometimes|required|string',
                'idn' => 'sometimes|required|string',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
            ]);

            $dzikirDoa->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Dzikir Doa updated successfully',
                'data' => $dzikirDoa
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update dzikir doa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Dzikir Doa
     */
    public function deleteDzikirDoa($id)
    {
        try {
            $dzikirDoa = DoaDzikir::findOrFail($id);
            $dzikirDoa->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Dzikir Doa deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete dzikir doa: ' . $e->getMessage()
            ], 500);
        }
    }
}