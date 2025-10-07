<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoaDzikir;
use App\Models\UserDoaDzikirSession;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DoaDzikirController extends Controller
{
    /**
     * Get all doa and dzikir with optional filtering
     */
    public function index(Request $request)
    {
        try {
            $query = DoaDzikir::active();

            // Filter by group if specified
            if ($request->has('grup') && !empty($request->grup)) {
                $query->byGrup($request->grup);
            }

            // Filter by tag if specified
            if ($request->has('tag') && !empty($request->tag)) {
                $query->byTag($request->tag);
            }

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $query->search($request->search);
            }

            // Featured only
            if ($request->has('featured') && $request->featured === 'true') {
                $query->featured();
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $doaDzikir = $query->orderBy('grup')
                             ->orderBy('nama')
                             ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Doa dan dzikir berhasil diambil',
                'doa_dzikir' => $doaDzikir->items(),
                'pagination' => [
                    'current_page' => $doaDzikir->currentPage(),
                    'last_page' => $doaDzikir->lastPage(),
                    'per_page' => $doaDzikir->perPage(),
                    'total' => $doaDzikir->total(),
                    'has_more_pages' => $doaDzikir->hasMorePages(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific doa/dzikir by ID
     */
    public function show($id)
    {
        try {
            $doaDzikir = DoaDzikir::active()->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detail doa/dzikir berhasil diambil',
                'doa_dzikir' => $doaDzikir
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Doa/dzikir tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get available groups for filtering
     */
    public function groups()
    {
        try {
            $groups = DoaDzikir::getGroups();

            return response()->json([
                'success' => true,
                'message' => 'Grup doa/dzikir berhasil diambil',
                'groups' => $groups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil grup',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available tags for filtering
     */
    public function tags()
    {
        try {
            $tags = DoaDzikir::getTags();

            return response()->json([
                'success' => true,
                'message' => 'Tag doa/dzikir berhasil diambil',
                'tags' => $tags
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a dzikir session
     */
    public function startSession(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doa_dzikir_id' => 'required|exists:doa_dzikir,id',
                'target_count' => 'nullable|integer|min:1',
                'mood_before' => 'nullable|in:senang,sedih,biasa_saja,marah,murung'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = UserDoaDzikirSession::create([
                'user_id' => Auth::id(),
                'doa_dzikir_id' => $request->doa_dzikir_id,
                'target_count' => $request->target_count,
                'mood_before' => $request->mood_before,
                'started_at' => now(),
            ]);

            $session->load('doaDzikir');

            // Log aktivitas untuk Sakinah Tracker
            event(new UserActivityEvent('dzikir_started', [
                'session_id' => $session->id,
                'dzikir_name' => $session->doaDzikir->nama,
                'target_count' => $session->target_count
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sesi dzikir dimulai',
                'session' => $session
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memulai sesi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update dzikir session progress
     */
    public function updateSession(Request $request, $sessionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'completed_count' => 'required|integer|min:0',
                'duration_seconds' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = UserDoaDzikirSession::where('id', $sessionId)
                                         ->where('user_id', Auth::id())
                                         ->firstOrFail();

            $session->update([
                'completed_count' => $request->completed_count,
                'duration_seconds' => $request->duration_seconds,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Progress sesi berhasil diperbarui',
                'session' => $session->fresh(['doaDzikir'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui sesi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete dzikir session
     */
    public function completeSession(Request $request, $sessionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'completed_count' => 'required|integer|min:0',
                'duration_seconds' => 'required|integer|min:0',
                'mood_after' => 'nullable|in:senang,sedih,biasa_saja,marah,murung,tenang,bersyukur',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = UserDoaDzikirSession::where('id', $sessionId)
                                         ->where('user_id', Auth::id())
                                         ->firstOrFail();

            $session->update([
                'completed_count' => $request->completed_count,
                'duration_seconds' => $request->duration_seconds,
                'mood_after' => $request->mood_after,
                'notes' => $request->notes,
                'completed' => true,
                'completed_at' => now(),
            ]);

            // Log aktivitas completion untuk Sakinah Tracker
            event(new UserActivityEvent('dzikir_completed', [
                'session_id' => $session->id,
                'dzikir_name' => $session->doaDzikir->nama,
                'completed_count' => $request->completed_count,
                'target_count' => $session->target_count,
                'duration_seconds' => $request->duration_seconds,
                'mood_before' => $session->mood_before,
                'mood_after' => $request->mood_after
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sesi dzikir berhasil diselesaikan',
                'session' => $session->fresh(['doaDzikir'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyelesaikan sesi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's dzikir sessions history
     */
    public function getUserSessions(Request $request)
    {
        try {
            $query = UserDoaDzikirSession::forUser(Auth::id())
                                       ->with('doaDzikir')
                                       ->orderBy('created_at', 'desc');

            // Filter by completion status
            if ($request->has('completed') && $request->completed !== null) {
                $completed = filter_var($request->completed, FILTER_VALIDATE_BOOLEAN);
                $query->where('completed', $completed);
            }

            // Date range filter
            if ($request->has('from_date')) {
                $query->whereDate('started_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('started_at', '<=', $request->to_date);
            }

            $perPage = $request->get('per_page', 15);
            $sessions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Riwayat sesi dzikir berhasil diambil',
                'sessions' => $sessions->items(),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                    'has_more_pages' => $sessions->hasMorePages(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil riwayat sesi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's dzikir statistics
     */
    public function getUserStats(Request $request)
    {
        try {
            $userId = Auth::id();

            $stats = [
                'total_sessions' => UserDoaDzikirSession::forUser($userId)->count(),
                'completed_sessions' => UserDoaDzikirSession::forUser($userId)->completed()->count(),
                'total_dzikir_count' => UserDoaDzikirSession::forUser($userId)->sum('completed_count'),
                'sessions_this_week' => UserDoaDzikirSession::forUser($userId)
                                                          ->where('started_at', '>=', now()->startOfWeek())
                                                          ->count(),
                'sessions_this_month' => UserDoaDzikirSession::forUser($userId)
                                                           ->where('started_at', '>=', now()->startOfMonth())
                                                           ->count(),
                'favorite_mood_after' => UserDoaDzikirSession::forUser($userId)
                                                            ->whereNotNull('mood_after')
                                                            ->selectRaw('mood_after, COUNT(*) as count')
                                                            ->groupBy('mood_after')
                                                            ->orderBy('count', 'desc')
                                                            ->first()?->mood_after,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistik dzikir berhasil diambil',
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}