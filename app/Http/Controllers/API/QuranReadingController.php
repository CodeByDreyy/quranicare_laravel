<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranReadingSession;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuranReadingController extends Controller
{
    /**
     * Start a Quran reading session
     */
    public function startSession(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'surah_number' => 'required|integer|min:1|max:114',
                'surah_name' => 'required|string|max:100',
                'ayah_start' => 'nullable|integer|min:1',
                'ayah_end' => 'nullable|integer|min:1',
                'reading_type' => 'required|in:full_surah,ayah_range,tilawah,memorization'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = QuranReadingSession::create([
                'user_id' => Auth::id(),
                'surah_number' => $request->surah_number,
                'surah_name' => $request->surah_name,
                'ayah_start' => $request->ayah_start,
                'ayah_end' => $request->ayah_end,
                'reading_type' => $request->reading_type,
                'started_at' => now(),
            ]);

            // Log aktivitas untuk Sakinah Tracker
            event(new UserActivityEvent('quran_reading_started', [
                'session_id' => $session->id,
                'surah_name' => $session->surah_name,
                'surah_number' => $session->surah_number,
                'reading_type' => $session->reading_type
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sesi baca Al-Quran dimulai',
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
     * Complete Quran reading session
     */
    public function completeSession(Request $request, $sessionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'duration_seconds' => 'required|integer|min:0',
                'pages_read' => 'nullable|integer|min:0',
                'verses_read' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = QuranReadingSession::where('id', $sessionId)
                                         ->where('user_id', Auth::id())
                                         ->firstOrFail();

            $session->update([
                'duration_seconds' => $request->duration_seconds,
                'pages_read' => $request->pages_read,
                'verses_read' => $request->verses_read,
                'notes' => $request->notes,
                'completed' => true,
                'completed_at' => now(),
            ]);

            // Log aktivitas completion untuk Sakinah Tracker
            event(new UserActivityEvent('quran_reading_completed', [
                'session_id' => $session->id,
                'surah_name' => $session->surah_name,
                'surah_number' => $session->surah_number,
                'reading_type' => $session->reading_type,
                'duration_seconds' => $request->duration_seconds,
                'pages_read' => $request->pages_read,
                'verses_read' => $request->verses_read
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sesi baca Al-Quran berhasil diselesaikan',
                'session' => $session
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
     * Get user's Quran reading history
     */
    public function getUserSessions(Request $request)
    {
        try {
            $query = QuranReadingSession::where('user_id', Auth::id());

            // Filter by completion status
            if ($request->has('completed')) {
                $query->where('completed', $request->boolean('completed'));
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('started_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('started_at', '<=', $request->to_date);
            }

            $perPage = $request->get('per_page', 15);
            $sessions = $query->orderBy('started_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Riwayat baca Al-Quran berhasil diambil',
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
                'message' => 'Terjadi kesalahan saat mengambil riwayat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's Quran reading statistics
     */
    public function getUserStats()
    {
        try {
            $userId = Auth::id();
            
            $stats = [
                'total_sessions' => QuranReadingSession::where('user_id', $userId)->count(),
                'completed_sessions' => QuranReadingSession::where('user_id', $userId)->where('completed', true)->count(),
                'total_duration' => QuranReadingSession::where('user_id', $userId)->sum('duration_seconds'),
                'total_pages' => QuranReadingSession::where('user_id', $userId)->sum('pages_read'),
                'total_verses' => QuranReadingSession::where('user_id', $userId)->sum('verses_read'),
                'this_month_sessions' => QuranReadingSession::where('user_id', $userId)
                    ->whereMonth('started_at', now()->month)
                    ->whereYear('started_at', now()->year)
                    ->count(),
                'this_week_sessions' => QuranReadingSession::where('user_id', $userId)
                    ->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistik baca Al-Quran berhasil diambil',
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