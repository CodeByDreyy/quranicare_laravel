<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        
        // Calendar & Date-based Mood Tracking
        Route::get('calendar/{year}/{month}', [MoodController::class, 'getCalendarMoods']);
        Route::get('calendar/range/{startDate}/{endDate}', [MoodController::class, 'getMoodsByDateRange']);
        Route::get('daily-summary/{date}', [MoodController::class, 'getDailySummary']);
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
// 6. TESTING ROUTES (Public Testing Endpoints)
// ============================================================================

// Railway Debug Route
Route::get('/debug-railway', function() {
    return response()->json([
        'message' => 'Railway Deployment Debug Info',
        'status' => 'SUCCESS ✅',
        'deployment_info' => [
            'environment' => env('APP_ENV'),
            'app_name' => env('APP_NAME'),
            'app_url' => env('APP_URL'),
            'database_host' => env('DB_HOST'),
            'php_version' => phpversion(),
            'laravel_version' => app()->version()
        ],
        'current_time' => now()->format('Y-m-d H:i:s T'),
        'request_info' => [
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ],
        'available_endpoints' => [
            '/api/halo',
            '/api/testing', 
            '/api/quranicare',
            '/api/test/health',
            '/api/debug-railway',
            '/api/debug-routes',
            '/api/auth/register',
            '/api/auth/login'
        ],
        'note' => 'Kalau ini muncul, berarti API Railway sudah jalan! 🎉'
    ]);
});

// Debug Routes untuk cek controller
Route::get('/debug-routes', function() {
    try {
        // Test if AuthController can be instantiated
        $authController = new \App\Http\Controllers\API\AuthController();
        $authControllerStatus = 'OK ✅';
    } catch (\Exception $e) {
        $authControllerStatus = 'ERROR ❌: ' . $e->getMessage();
    }
    
    // Get all registered routes
    $routes = [];
    foreach (Route::getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'api/')) {
            $routes[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName()
            ];
        }
    }
    
    return response()->json([
        'message' => 'Routes Debug Information',
        'controller_tests' => [
            'AuthController' => $authControllerStatus
        ],
        'auth_routes_registered' => [
            'POST /api/auth/register' => 'Should work',
            'POST /api/auth/login' => 'Should work',
            'POST /api/auth/logout' => 'Should work (with auth)',
            'GET /api/auth/me' => 'Should work (with auth)'
        ],
        'total_registered_routes' => count($routes),
        'sample_routes' => array_slice($routes, 0, 10),
        'timestamp' => now()->format('Y-m-d H:i:s')
    ]);
});

// ============================================================================
// DIRECT LOGIN & REGISTER ROUTES (Railway Fix)
// ============================================================================

// Direct Login Route
Route::post('/login', function (Request $request) {
    try {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
                'errors' => ['auth' => 'Kredensial tidak valid']
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('QuraniCareToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil! Selamat datang kembali 🌙',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Data tidak valid',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Direct Register Route
Route::post('/register', function (Request $request) {
    try {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',
            'location' => 'nullable|string|max:255'
        ]);

        $user = \App\Models\User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'gender' => $validatedData['gender'] ?? 'male',
            'birth_date' => $validatedData['birth_date'] ?? null,
            'location' => $validatedData['location'] ?? null,
            'profile_picture' => null,
            'bio' => 'Assalamu\'alaikum, saya pengguna baru QuraniCare 🌙',
        ]);

        $token = $user->createToken('QuraniCareToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuat! Selamat bergabung dengan QuraniCare 🌙✨',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'birth_date' => $user->birth_date,
                    'location' => $user->location,
                    'created_at' => $user->created_at
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Data tidak valid',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Direct Logout Route
Route::post('/logout', function (Request $request) {
    try {
        $user = $request->user();
        
        if ($user) {
            // Revoke current token
            $user->tokens()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil. Barakallahu fiikum! 🤲'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ], 401);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan server',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum');

// User Profile Route
Route::get('/me', function (Request $request) {
    try {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->gender,
                'birth_date' => $user->birth_date,
                'location' => $user->location,
                'bio' => $user->bio,
                'profile_picture' => $user->profile_picture,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data user',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum');

// ============================================================================
// MOOD CALENDAR API (Direct Routes for Calendar Integration)
// ============================================================================

// Get Mood Calendar Data for specific month/year
Route::get('/mood-calendar/{year}/{month}', function (Request $request, $year, $month) {
    try {
        $user = $request->user();
        
        // Get all moods for the specified month
        $moods = \App\Models\Mood::where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($mood) {
                return $mood->created_at->format('Y-m-d');
            });

        // Format data for calendar
        $calendarData = [];
        foreach ($moods as $date => $dayMoods) {
            $calendarData[$date] = [
                'date' => $date,
                'mood_count' => $dayMoods->count(),
                'moods' => $dayMoods->map(function($mood) {
                    return [
                        'id' => $mood->id,
                        'type' => $mood->type,
                        'level' => $mood->level,
                        'note' => $mood->note,
                        'time' => $mood->created_at->format('H:i'),
                        'timestamp' => $mood->created_at->toISOString()
                    ];
                }),
                'dominant_mood' => $dayMoods->groupBy('type')->sortByDesc(function($group) {
                    return $group->count();
                })->keys()->first(),
                'average_level' => round($dayMoods->avg('level'), 1)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'month' => (int) $month,
                'calendar_data' => $calendarData,
                'total_days_with_mood' => count($calendarData),
                'total_mood_entries' => $moods->flatten()->count()
            ],
            'message' => 'Data mood kalender berhasil diambil untuk ' . date('F Y', mktime(0, 0, 0, $month, 1, $year))
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data mood kalender',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum');

// Get Mood Data for specific date
Route::get('/mood-daily/{date}', function (Request $request, $date) {
    try {
        $user = $request->user();
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Format tanggal tidak valid. Gunakan format: YYYY-MM-DD'
            ], 400);
        }

        // Get all moods for the specific date
        $moods = \App\Models\Mood::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate statistics
        $moodStats = [
            'total_entries' => $moods->count(),
            'mood_types' => $moods->groupBy('type')->map(function($group, $type) {
                return [
                    'count' => $group->count(),
                    'average_level' => round($group->avg('level'), 1),
                    'entries' => $group->map(function($mood) {
                        return [
                            'id' => $mood->id,
                            'level' => $mood->level,
                            'note' => $mood->note,
                            'time' => $mood->created_at->format('H:i')
                        ];
                    })
                ];
            }),
            'overall_average' => $moods->count() > 0 ? round($moods->avg('level'), 1) : 0,
            'mood_trend' => $this->calculateMoodTrend($moods)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'moods' => $moods->map(function($mood) {
                    return [
                        'id' => $mood->id,
                        'type' => $mood->type,
                        'level' => $mood->level,
                        'note' => $mood->note,
                        'created_at' => $mood->created_at->toISOString(),
                        'time' => $mood->created_at->format('H:i:s')
                    ];
                }),
                'statistics' => $moodStats
            ],
            'message' => $moods->count() > 0 
                ? "Ditemukan {$moods->count()} entri mood pada tanggal {$date}" 
                : "Tidak ada data mood pada tanggal {$date}"
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data mood harian',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum');

// Get Mood Data for date range
Route::get('/mood-range/{startDate}/{endDate}', function (Request $request, $startDate, $endDate) {
    try {
        $user = $request->user();
        
        // Validate date formats
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || 
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Format tanggal tidak valid. Gunakan format: YYYY-MM-DD'
            ], 400);
        }

        // Get moods within date range
        $moods = \App\Models\Mood::where('user_id', $user->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($mood) {
                return $mood->created_at->format('Y-m-d');
            });

        // Format data by date
        $dateRangeData = [];
        foreach ($moods as $date => $dayMoods) {
            $dateRangeData[$date] = [
                'date' => $date,
                'day_name' => \Carbon\Carbon::parse($date)->locale('id')->dayName,
                'mood_count' => $dayMoods->count(),
                'dominant_mood' => $dayMoods->groupBy('type')->sortByDesc(function($group) {
                    return $group->count();
                })->keys()->first(),
                'average_level' => round($dayMoods->avg('level'), 1),
                'moods' => $dayMoods->map(function($mood) {
                    return [
                        'id' => $mood->id,
                        'type' => $mood->type,
                        'level' => $mood->level,
                        'note' => $mood->note,
                        'time' => $mood->created_at->format('H:i')
                    ];
                })
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'range_data' => $dateRangeData,
                'summary' => [
                    'total_days_with_data' => count($dateRangeData),
                    'total_mood_entries' => $moods->flatten()->count(),
                    'overall_average' => $moods->flatten()->count() > 0 ? 
                        round($moods->flatten()->avg('level'), 1) : 0
                ]
            ],
            'message' => "Data mood dari {$startDate} hingga {$endDate} berhasil diambil"
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil data mood untuk rentang tanggal',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum');

// Test Authentication Routes (Alternative)
Route::prefix('test-auth')->group(function() {
    Route::post('register', function(Request $request) {
        try {
            $controller = new \App\Http\Controllers\API\AuthController();
            return $controller->register($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Controller error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
    
    Route::post('login', function(Request $request) {
        try {
            $controller = new \App\Http\Controllers\API\AuthController();
            return $controller->login($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Controller error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
});

// Fun Testing Routes
Route::get('/halo', function() {
    return response()->json([
        'message' => 'Halo! QuraniCare API siap melayani! 🌙✨',
        'status' => 'success',
        'app' => env('APP_NAME', 'QuraniCare'),
        'version' => '1.0.0',
        'environment' => env('APP_ENV', 'production'),
        'time' => now()->format('Y-m-d H:i:s'),
        'islamic_greeting' => 'Assalamu\'alaikum Warahmatullahi Wabarakatuh',
        'developer' => 'CodeByDreyy',
        'fun_fact' => 'API ini dibuat dengan ❤️ untuk kesehatan mental umat Islam'
    ]);
});

Route::get('/testing', function() {
    return response()->json([
        'message' => 'QuraniCare API Testing Endpoint! 🚀',
        'status' => 'active',
        'endpoints_available' => [
            'GET /api/halo' => 'Greeting endpoint',
            'GET /api/testing' => 'This endpoint',
            'GET /api/quranicare' => 'App info & stats',
            'GET /api/test/health' => 'Health check',
            'GET /api/test/database' => 'Database connection test',
            'GET /api/test/features' => 'Available features list',
            'POST /api/chat' => 'Gemini AI Chat',
            'POST /api/auth/register' => 'User registration',
            'POST /api/auth/login' => 'User login'
        ],
        'database_status' => 'connected ✅',
        'last_test' => now()->format('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone')
        ]
    ]);
});


Route::get('/quranicare', function() {
    // Get some basic stats
    $userCount = \App\Models\User::count();
    $moodCount = \App\Models\Mood::count();
    $journalCount = \App\Models\Journal::count();
    $audioCategoryCount = \App\Models\AudioCategory::count();
    $dzikirCount = \App\Models\DoaDzikir::count();
    
    return response()->json([
        'app_name' => 'QuraniCare - Islamic Mental Health App',
        'tagline' => 'Menyembuhkan Jiwa dengan Cahaya Al-Quran 🌙',
        'status' => 'Alhamdulillah, berjalan dengan baik! 🤲',
        'features' => [
            '🕌 Quran Reading & Reflection',
            '📿 Dzikir & Doa Daily',
            '🧘‍♂️ Breathing Exercises (Nafas Sakinah)',
            '🎵 Audio Relaxation Islami',
            '📔 Journal Refleksi Spiritual',
            '😊 Mood Tracking Harian',
            '🤖 Qalbu AI Assistant',
            '📚 Psychology Learning Islamic',
            '📊 Sakinah Tracker Dashboard'
        ],
        'statistics' => [
            'total_users' => $userCount,
            'mood_entries' => $moodCount,
            'journal_reflections' => $journalCount,
            'audio_categories' => $audioCategoryCount,
            'dzikir_doa_items' => $dzikirCount
        ],
        'api_info' => [
            'version' => '1.0.0',
            'environment' => env('APP_ENV'),
            'last_updated' => '2025-10-07',
            'developer' => 'CodeByDreyy',
            'github' => 'https://github.com/CodeByDreyy/quranicare_laravel'
        ],
        'islamic_quote' => 'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا - "Dan barangsiapa bertakwa kepada Allah, niscaya Dia akan mengadakan baginya jalan keluar" (QS. At-Talaq: 2)',
        'timestamp' => now()->format('Y-m-d H:i:s T'),
        'message' => 'Juara!! API QuraniCare ready to serve! Barakallahu fiikum!'
    ]);
});

// Detailed Testing Routes
Route::prefix('test')->group(function () {
    
    // Health Check
    Route::get('health', function() {
        try {
            // Test database connection
            DB::connection()->getPdo();
            $dbStatus = 'connected ✅';
        } catch (\Exception $e) {
            $dbStatus = 'error ❌: ' . $e->getMessage();
        }
        
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'checks' => [
                'api' => 'OK ✅',
                'database' => $dbStatus,
                'cache' => cache()->remember('health_test', 60, fn() => 'OK ✅'),
                'session' => session()->isStarted() ? 'active ✅' : 'inactive ⚠️',
                'storage' => is_writable(storage_path()) ? 'writable ✅' : 'readonly ⚠️'
            ],
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'uptime' => 'Since deployment',
            'message' => 'Alhamdulillah, semua sistem berjalan baik! 🤲'
        ]);
    });
    
    // Database Test
    Route::get('database', function() {
        try {
            $tests = [];
            
            // Test each table
            $tables = [
                'users' => \App\Models\User::count(),
                'moods' => \App\Models\Mood::count(),
                'journals' => \App\Models\Journal::count(),
                'audio_categories' => \App\Models\AudioCategory::count(),
                'doa_dzikir' => \App\Models\DoaDzikir::count(),
                'quran_surahs' => \App\Models\QuranSurah::count(),
                'quran_ayahs' => \App\Models\QuranAyah::count(),
                'breathing_exercises' => \App\Models\BreathingExercise::count(),
                'psychology_materials' => \App\Models\PsychologyMaterial::count()
            ];
            
            foreach ($tables as $table => $count) {
                $tests[$table] = [
                    'status' => 'OK ✅',
                    'record_count' => $count
                ];
            }
            
            return response()->json([
                'database_status' => 'All tables accessible ✅',
                'connection' => 'MySQL connected to ' . config('database.connections.mysql.host'),
                'tables' => $tests,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'message' => 'Database sehat walafiat! 💪'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'database_status' => 'Error ❌',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 500);
        }
    });
    
    // Features List
    Route::get('features', function() {
        return response()->json([
            'app_features' => [
                'authentication' => [
                    'register' => '/api/auth/register',
                    'login' => '/api/auth/login',
                    'logout' => '/api/auth/logout',
                    'forgot_password' => '/api/auth/forgot-password'
                ],
                'quran' => [
                    'get_surahs' => '/api/public/quran/surahs',
                    'get_ayahs' => '/api/public/quran/surahs/{id}/ayahs',
                    'search' => '/api/public/quran/search',
                    'bookmarks' => '/api/quran/bookmarks [AUTH]'
                ],
                'mood_tracking' => [
                    'record_mood' => '/api/mood [POST, AUTH]',
                    'mood_history' => '/api/mood/history [AUTH]',
                    'mood_stats' => '/api/mood/statistics [AUTH]'
                ],
                'journal' => [
                    'create_journal' => '/api/journal [POST, AUTH]',
                    'ayah_reflection' => '/api/journal/ayah/{id}/reflection [AUTH]',
                    'recent_reflections' => '/api/journal/reflections/recent [AUTH]'
                ],
                'dzikir_doa' => [
                    'get_all' => '/api/doa-dzikir',
                    'by_groups' => '/api/doa-dzikir/groups',
                    'start_session' => '/api/doa-dzikir/sessions [POST, AUTH]'
                ],
                'audio_relax' => [
                    'get_categories' => '/api/audio-categories',
                    'get_audio' => '/api/audio-relax/{id}',
                    'play_tracking' => '/api/audio/{id}/play [POST, AUTH]'
                ],
                'breathing' => [
                    'get_exercises' => '/api/breathing-exercise/exercises/{id}',
                    'start_session' => '/api/breathing/sessions [POST, AUTH]'
                ],
                'ai_chat' => [
                    'gemini_chat' => '/api/chat [POST]',
                    'qalbu_assistant' => '/api/qalbu/conversations [AUTH]'
                ],
                'psychology' => [
                    'get_materials' => '/api/public/psychology/materials/{id}',
                    'update_progress' => '/api/psychology/materials/{id}/progress [POST, AUTH]'
                ],
                'sakinah_tracker' => [
                    'daily_recap' => '/api/sakinah-tracker/daily/{date} [AUTH]',
                    'monthly_stats' => '/api/sakinah-tracker/monthly/{year}/{month} [AUTH]',
                    'dashboard' => '/api/sakinah-tracker/dashboard-summary [AUTH]'
                ]
            ],
            'testing_endpoints' => [
                'health_check' => '/api/test/health',
                'database_test' => '/api/test/database',
                'app_info' => '/api/quranicare',
                'greeting' => '/api/halo'
            ],
            'note' => '[AUTH] = Requires Authorization Bearer Token',
            'message' => 'Lengkap kan fiturnya? Alhamdulillah! 🎉',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
    });
    
    // Original Journal Testing Routes
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