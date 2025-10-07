<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MoodController;
use App\Http\Controllers\API\BreathingController;
use App\Http\Controllers\API\AudioController;
use App\Http\Controllers\API\QuranController;
use App\Http\Controllers\API\JournalController;
use App\Http\Controllers\API\DzikirController;
use App\Http\Controllers\DzikirDoaController;
use App\Http\Controllers\DzikirCategoryController;
use App\Http\Controllers\API\DoaDzikirController;
use App\Http\Controllers\AudioRelaxController;
use App\Http\Controllers\AudioCategoryController;
use App\Http\Controllers\BreathingExerciseController;
use App\Http\Controllers\API\QalbuChatController;
use App\Http\Controllers\API\PsychologyController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AdminDashboardController;
use App\Http\Controllers\QalbuChatbotController;
use App\Http\Controllers\Api\SakinahTrackerController;
use App\Http\Controllers\Api\QuranReadingController;

/*
|--------------------------------------------------------------------------
| API Routes - QuraniCare Islamic Mental Health App
|--------------------------------------------------------------------------
*/



// ============================================================================
// 1. GEMINI CHAT API (Public Access)
// ============================================================================
Route::post('/chat', function (Request $request) {
    $userMessage = $request->input('message');

    // Prompt Islami - Natural & Casual
    $systemPrompt = "
Kamu adalah teman yang baik dan punya pengetahuan tentang Islam dan kesehatan mental. Tugas kamu adalah ngobrol santai dan memberikan dukungan dengan pendekatan Islami.

Cara ngobrol kamu:
- Bahasa santai, hangat, dan natural seperti teman dekat
- Jangan pakai simbol ** atau emoji berlebihan
- Jawaban singkat dan to the point, jangan bertele-tele
- Empati dan pengertian, tapi tetap natural

Kalau mau kasih dalil:
- Pilih SATU aja: kalau pakai ayat Quran ya ayat aja, kalau hadits ya hadits aja
- Cuma kasih kalau memang relevan banget, jangan dipaksain
- Sebutkan sumber dengan jelas (contoh: QS. Al-Baqarah: 286 atau HR. Bukhari)
- Jangan pernah bikin-bikin ayat atau hadits palsu

Jangan:
- Diagnosa medis
- Jawab panjang banget
- Pakai format kaku atau numbering
- Pakai bahasa formal berlebihan

Kalau ada masalah serius, saranin konsultasi sama psikolog muslim dengan cara yang natural.

Intinya: jadi teman yang supportif dengan wisdom Islam, tapi tetap santai dan enak diajak ngobrol.
    ";

    $apiKey = env('GEMINI_API_KEY');

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-goog-api-key' => $apiKey,
    ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent", [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemPrompt],
                    ["text" => $userMessage]
                ]
            ]
        ]
    ]);

    if ($response->successful()) {
        return response()->json([
            'reply' => $response->json()["candidates"][0]["content"]["parts"][0]["text"]
        ]);
    } else {
        return response()->json([
            'error' => $response->body()
        ], 500);
    }
});

// ============================================================================
// 2. AUTHENTICATION ROUTES
// ============================================================================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// ============================================================================
// 3. PUBLIC ROUTES (No Authentication Required)
// ============================================================================
Route::prefix('public')->group(function () {
    
    // 3.1 Quran Public Routes
    Route::prefix('quran')->group(function () {
        Route::get('surahs', [QuranController::class, 'getSurahs']);
        Route::get('surahs/{surah}/ayahs', [QuranController::class, 'getAyahs']);
        Route::get('ayahs/{ayah}', [QuranController::class, 'getAyah']);
        Route::get('search', [QuranController::class, 'search']);
    });
    
    // 3.2 Dzikir Public Routes
    Route::prefix('dzikir')->group(function () {
        Route::get('categories', [DzikirController::class, 'getCategories']);
        Route::get('{category}/items', [DzikirController::class, 'getDzikirByCategory']);
    });
    
    // 3.3 Psychology Public Routes
    Route::prefix('psychology')->group(function () {
        Route::get('categories', [PsychologyController::class, 'getCategories']);
        Route::get('{category}/materials', [PsychologyController::class, 'getMaterials']);
        Route::get('materials/{material}', [PsychologyController::class, 'getMaterial']);
    });
    
    // 3.4 Audio Public Routes
    Route::prefix('audio')->group(function () {
        Route::get('categories', [AudioController::class, 'getCategories']);
        Route::get('/', [AudioController::class, 'index']);
        Route::get('{audio}', [AudioController::class, 'show']);
    });
    
    // 3.5 Breathing Exercise Public Routes
    Route::prefix('breathing')->group(function () {
        Route::get('categories', [BreathingController::class, 'getCategories']);
        Route::get('exercises', [BreathingController::class, 'getExercises']);
        Route::get('exercises/{exercise}', [BreathingController::class, 'getExercise']);
    });
});

// ============================================================================
// 4. PUBLIC QALBU CHATBOT PROXY (No Authentication)
// ============================================================================
// Expose chatbot proxy publicly so mobile/web can call without auth during chat
Route::prefix('qalbu')->group(function () {
    Route::post('chatbot', [QalbuChatbotController::class, 'chat']);
    Route::post('chatbot/feedback', [QalbuChatbotController::class, 'feedback']);
});

// ============================================================================
// 5. PROTECTED ROUTES (Authentication Required)
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {
    
    // 5.1 User Profile & Dashboard
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'getProfile']);
        Route::get('profile/refresh', [UserController::class, 'refreshProfile']);
        Route::get('greeting', [UserController::class, 'getGreeting']);
        Route::get('debug', [UserController::class, 'debugUser']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('profile/picture', [UserController::class, 'updateProfilePicture']);
        Route::get('dashboard', [UserController::class, 'getDashboard']);
        Route::get('statistics', [UserController::class, 'getStatistics']);
    });

    // 5.2 Mood Tracking
    Route::prefix('mood')->group(function () {
        Route::get('/', [MoodController::class, 'index']);
        Route::post('/', [MoodController::class, 'store']);
        Route::get('today', [MoodController::class, 'getTodayMoods']);
        Route::get('statistics', [MoodController::class, 'getStatistics']);
        Route::get('history', [MoodController::class, 'getHistory']);
        Route::put('{mood}', [MoodController::class, 'update']);
        Route::delete('{mood}', [MoodController::class, 'destroy']);
    });

    // 5.3 Breathing Exercises
    Route::prefix('breathing')->group(function () {
        Route::post('sessions', [BreathingController::class, 'startSession']);
        Route::put('sessions/{session}', [BreathingController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [BreathingController::class, 'completeSession']);
        Route::get('sessions/history', [BreathingController::class, 'getSessionHistory']);
    });

    // 5.4 Audio Relaxation
    Route::prefix('audio')->group(function () {
        Route::post('{audio}/play', [AudioController::class, 'recordPlay']);
        Route::post('sessions', [AudioController::class, 'startSession']);
        Route::put('sessions/{session}', [AudioController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [AudioController::class, 'completeSession']);
        Route::post('{audio}/rate', [AudioController::class, 'rateAudio']);
    });

    // 5.5 Quran Features (Protected)
    Route::prefix('quran')->group(function () {
        Route::post('ayahs/{ayah}/bookmark', [QuranController::class, 'toggleBookmark']);
        Route::get('bookmarks', [QuranController::class, 'getBookmarks']);
    });

    // 5.6 Journal & Reflection System
    Route::prefix('journal')->group(function () {
        // Basic Journal CRUD
        Route::get('/', [JournalController::class, 'index']);
        Route::post('/', [JournalController::class, 'store']);
        Route::get('{journal}', [JournalController::class, 'show']);
        Route::put('{journal}', [JournalController::class, 'update']);
        Route::delete('{journal}', [JournalController::class, 'destroy']);
        Route::post('{journal}/favorite', [JournalController::class, 'toggleFavorite']);
        
        // Journal Utilities
        Route::get('tags/suggestions', [JournalController::class, 'getTagSuggestions']);
        
        // Quran Reflection System
        Route::get('ayah/{ayah}', [JournalController::class, 'getAyahReflections']);
        Route::post('ayah/{ayah}/reflection', [JournalController::class, 'createAyahReflection']);
        Route::get('reflections/recent', [JournalController::class, 'getRecentReflections']);
        Route::get('reflections/stats', [JournalController::class, 'getReflectionStats']);
    });

    // 5.7 Dzikir & Spiritual Practices (Old Structure)
    Route::prefix('dzikir')->group(function () {
        Route::post('sessions', [DzikirController::class, 'startSession']);
        Route::put('sessions/{session}', [DzikirController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [DzikirController::class, 'completeSession']);
        Route::get('sessions/history', [DzikirController::class, 'getSessionHistory']);
        Route::post('{dzikir}/favorite', [DzikirController::class, 'toggleFavorite']);
    });

    // 5.7.1 Doa Dzikir Sessions (New Structure)
    Route::prefix('doa-dzikir')->group(function () {
        Route::post('sessions', [DoaDzikirController::class, 'startSession']);
        Route::put('sessions/{session}', [DoaDzikirController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [DoaDzikirController::class, 'completeSession']);
        Route::get('sessions/history', [DoaDzikirController::class, 'getUserSessions']);
        Route::get('stats', [DoaDzikirController::class, 'getUserStats']);
    });

    // 5.7.2 Quran Reading Sessions
    Route::prefix('quran-reading')->group(function () {
        Route::post('sessions', [QuranReadingController::class, 'startSession']);
        Route::post('sessions/{session}/complete', [QuranReadingController::class, 'completeSession']);
        Route::get('sessions/history', [QuranReadingController::class, 'getUserSessions']);
        Route::get('stats', [QuranReadingController::class, 'getUserStats']);
    });

    // 5.7.3 Sakinah Tracker (Activity Tracking & Daily Recap)
    Route::prefix('sakinah-tracker')->group(function () {
        Route::get('daily/{date?}', [SakinahTrackerController::class, 'getDailyActivities']);
        Route::get('monthly/{year}/{month}', [SakinahTrackerController::class, 'getMonthlyRecap']);
        Route::get('weekly', [SakinahTrackerController::class, 'getWeeklyStats']);
        Route::get('calendar/{year}/{month}', [SakinahTrackerController::class, 'getCalendarData']);
        Route::get('activity-summary', [SakinahTrackerController::class, 'getActivitySummary']);
        Route::get('streak', [SakinahTrackerController::class, 'getStreak']);
    });

    // 5.8 AI Chat (Qalbu Assistant)
    Route::prefix('qalbu')->group(function () {
        Route::get('conversations', [QalbuChatController::class, 'getConversations']);
        Route::post('conversations', [QalbuChatController::class, 'createConversation']);
        Route::get('conversations/{conversation}', [QalbuChatController::class, 'getConversation']);
        Route::post('conversations/{conversation}/messages', [QalbuChatController::class, 'sendMessage']);
        Route::put('conversations/{conversation}', [QalbuChatController::class, 'updateConversation']);
        Route::delete('conversations/{conversation}', [QalbuChatController::class, 'deleteConversation']);
        Route::post('messages/{message}/feedback', [QalbuChatController::class, 'provideFeedback']);
    });

    // 5.9 Psychology Learning
    Route::prefix('psychology')->group(function () {
        Route::post('materials/{material}/progress', [PsychologyController::class, 'updateProgress']);
        Route::post('materials/{material}/bookmark', [PsychologyController::class, 'toggleBookmark']);
        Route::post('materials/{material}/rate', [PsychologyController::class, 'rateMaterial']);
        Route::get('progress', [PsychologyController::class, 'getProgress']);
    });

    // 5.10 Favorites & Bookmarks
    Route::prefix('favorites')->group(function () {
        Route::get('/', [UserController::class, 'getFavorites']);
        Route::post('/', [UserController::class, 'addToFavorites']);
        Route::delete('{favorite}', [UserController::class, 'removeFromFavorites']);
    });

    // 5.11 Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [UserController::class, 'getNotifications']);
        Route::post('{notification}/read', [UserController::class, 'markNotificationAsRead']);
        Route::post('mark-all-read', [UserController::class, 'markAllNotificationsAsRead']);
    });
});

// ============================================================================
// 6. TESTING ROUTES (Public Journal - Remove in Production)
// ============================================================================
Route::prefix('test')->group(function () {
    Route::prefix('journal')->group(function () {
        Route::get('ayah/{ayah}', [JournalController::class, 'getAyahReflections']);
        Route::post('ayah/{ayah}/reflection', [JournalController::class, 'createAyahReflection']);
        Route::get('reflections/recent', [JournalController::class, 'getRecentReflections']);
        Route::get('reflections/stats', [JournalController::class, 'getReflectionStats']);
        Route::get('tags/suggestions', [JournalController::class, 'getTagSuggestions']);
    });
});

// ============================================================================
// 7. DOA DZIKIR ROUTES (Public Access) - New API Structure
// ============================================================================
Route::prefix('doa-dzikir')->group(function () {
    Route::get('/', [DoaDzikirController::class, 'index']);
    Route::get('groups', [DoaDzikirController::class, 'groups']);
    Route::get('tags', [DoaDzikirController::class, 'tags']);
    Route::get('{id}', [DoaDzikirController::class, 'show']);
});

// ============================================================================
// 7.1 DZIKIR DOA ROUTES (Old Structure - Backward Compatibility) 
// ============================================================================
Route::prefix('dzikir-doa')->group(function () {
    // Redirect to new API structure
    Route::get('/', [DoaDzikirController::class, 'index']);
    Route::get('groups', [DoaDzikirController::class, 'groups']);
    Route::get('tags', [DoaDzikirController::class, 'tags']);
    Route::get('featured', [DoaDzikirController::class, 'index']);
    Route::get('search', [DoaDzikirController::class, 'index']); 
    Route::get('category/{categoryId}', [DoaDzikirController::class, 'index']);
    Route::get('{id}', [DoaDzikirController::class, 'show']);
});

Route::prefix('dzikir-categories')->group(function () {
    Route::get('/', [DzikirCategoryController::class, 'index']);
    Route::get('{id}', [DzikirCategoryController::class, 'show']);
});

// ============================================================================
// 7.5 TEST SAKINAH TRACKER (Public for testing)
// ============================================================================
Route::get('test-sakinah-tracker', function() {
    $user = \App\Models\User::first();
    if (!$user) {
        return response()->json(['error' => 'No user found'], 404);
    }
    
    $activities = \App\Models\UserActivityLog::where('user_id', $user->id)
        ->whereDate('activity_date', today())
        ->get();
    
    $monthlyStats = \App\Models\UserActivityLog::where('user_id', $user->id)
        ->whereMonth('activity_date', now()->month)
        ->selectRaw('activity_type, COUNT(*) as count, SUM(duration_seconds) as total_duration')
        ->groupBy('activity_type')
        ->get();
    
    return response()->json([
        'user' => $user->name,
        'date' => today()->format('Y-m-d'),
        'today_activities_count' => $activities->count(),
        'activities_today' => $activities->groupBy('activity_type')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_duration' => $group->sum('duration_seconds'),
                'activities' => $group->map(function($activity) {
                    return [
                        'title' => $activity->activity_title,
                        'time' => $activity->activity_time,
                        'icon' => $activity->activity_icon
                    ];
                })
            ];
        }),
        'monthly_stats' => $monthlyStats
    ]);
});

// ============================================================================
// 8. AUDIO RELAX ROUTES (Public Access)
// ============================================================================
Route::prefix('audio-relax')->group(function () {
    Route::get('popular', [AudioRelaxController::class, 'popular']);
    Route::get('search', [AudioRelaxController::class, 'search']);
    Route::get('category/{categoryId}', [AudioRelaxController::class, 'getByCategory']);
    Route::get('{id}', [AudioRelaxController::class, 'show']);
    Route::post('{id}/play', [AudioRelaxController::class, 'updatePlayCount']);
    Route::post('{id}/rate', [AudioRelaxController::class, 'rateAudio']);
});

Route::prefix('audio-categories')->group(function () {
    Route::get('/', [AudioCategoryController::class, 'index']);
    Route::get('{id}', [AudioCategoryController::class, 'show']);
});

// ============================================================================
// 9. BREATHING EXERCISE ROUTES (Integrated with Database)
// ============================================================================
Route::prefix('breathing-exercise')->group(function () {
    // Public routes - get categories and exercises
    Route::get('categories', [BreathingExerciseController::class, 'getCategories']);
    Route::get('categories/{categoryId}/exercises', [BreathingExerciseController::class, 'getExercisesByCategory']);
    Route::get('exercises/{exerciseId}', [BreathingExerciseController::class, 'getExercise']);
    
    // Protected routes - sessions and user data
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('sessions', [BreathingExerciseController::class, 'startSession']);
        Route::put('sessions/{sessionId}/progress', [BreathingExerciseController::class, 'updateSessionProgress']);
        Route::post('sessions/{sessionId}/complete', [BreathingExerciseController::class, 'completeSession']);
        Route::get('users/{userId}/sessions', [BreathingExerciseController::class, 'getUserSessions']);
        Route::get('users/{userId}/stats', [BreathingExerciseController::class, 'getUserStats']);
    });
});

// ============================================================================
// 10. SAKINAH TRACKER API - Activity Tracking & Analytics
// ============================================================================
Route::prefix('sakinah-tracker')->middleware('auth:sanctum')->group(function () {
    // Daily & Monthly Views
    Route::get('daily/{date}', [SakinahTrackerController::class, 'getDailyActivities']);
    Route::get('monthly/{year}/{month}', [SakinahTrackerController::class, 'getMonthlyRecap']);
    Route::get('weekly/{startDate}', [SakinahTrackerController::class, 'getWeeklyActivities']);
    Route::get('calendar/{year}/{month}', [SakinahTrackerController::class, 'getCalendarData']);
    Route::get('summary', [SakinahTrackerController::class, 'getActivitySummary']);
    
    // Activity Logging
    Route::post('log-activity', [SakinahTrackerController::class, 'logActivity']);
    Route::get('activity-history', [SakinahTrackerController::class, 'getActivityHistory']);
    
    // Streaks & Motivation
    Route::get('streaks', [SakinahTrackerController::class, 'getStreaks']);
    Route::get('dashboard-summary', [SakinahTrackerController::class, 'getDashboardSummary']);
    
    // Insights & Analytics
    Route::get('insights', [SakinahTrackerController::class, 'getInsights']);
    
    // Data Migration (one-time)
    Route::post('sync-existing-data', [SakinahTrackerController::class, 'syncExistingData']);
});

// ============================================================================
// 11. DAILY RECAP API - Mood & Emotion Tracking
// ============================================================================
Route::prefix('daily-recap')->group(function () {
    // Daily mood recap
    Route::get('{date}', [App\Http\Controllers\API\DailyRecapController::class, 'getDailyRecap'])->middleware('auth:sanctum');
    Route::get('monthly/{year}/{month}', [App\Http\Controllers\API\DailyRecapController::class, 'getMonthlyMoodOverview'])->middleware('auth:sanctum');
});

// ============================================================================
// 11. QURAN READING SESSIONS (New Tracking)
// ============================================================================
Route::prefix('quran-sessions')->middleware('auth:sanctum')->group(function () {
    // Start reading session
    Route::post('start', function (Request $request) {
        // Endpoint untuk start Quran reading session
        // Akan diimplementasi setelah UI ready
    });
    
    // Complete reading session
    Route::post('{sessionId}/complete', function (Request $request, $sessionId) {
        // Endpoint untuk complete Quran reading session
        // Akan diimplementasi setelah UI ready
    });
    
    // Get user's reading history
    Route::get('history', function (Request $request) {
        // Get user's Quran reading sessions
        // Akan diimplementasi setelah UI ready
    });
});

// ============================================================================
// 12. ADMIN ROUTES
// ============================================================================
Route::prefix('admin')->group(function () {
    // Public admin routes
    Route::post('login', [AdminController::class, 'login']);
    
    // Protected admin routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AdminController::class, 'me']);
        Route::post('logout', [AdminController::class, 'logout']);
        Route::get('/', [AdminController::class, 'index']); // Get all admins
        Route::post('/', [AdminController::class, 'store']); // Create new admin
        
        // Dashboard endpoints
        Route::get('dashboard/stats', [AdminDashboardController::class, 'getDashboardStats']);
        Route::get('dashboard/dzikir-doa', [AdminDashboardController::class, 'getDzikirDoaList']);
        Route::get('dashboard/audio-relax', [AdminDashboardController::class, 'getAudioRelaxList']);
        Route::get('dashboard/psychology', [AdminDashboardController::class, 'getPsychologyMaterialsList']);
        Route::get('dashboard/notifications', [AdminDashboardController::class, 'getNotificationsList']);
        
        // CRUD endpoints for dzikir doa management
        Route::post('dzikir-doa', [AdminDashboardController::class, 'createDzikirDoa']);
        Route::put('dzikir-doa/{id}', [AdminDashboardController::class, 'updateDzikirDoa']);
        Route::delete('dzikir-doa/{id}', [AdminDashboardController::class, 'deleteDzikirDoa']);
        Route::get('dzikir-doa/{id}', [AdminDashboardController::class, 'getDzikirDoaDetail']);
    });
});