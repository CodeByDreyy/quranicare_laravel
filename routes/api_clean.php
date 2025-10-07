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
use App\Http\Controllers\AudioRelaxController;
use App\Http\Controllers\AudioCategoryController;
use App\Http\Controllers\BreathingExerciseController;
use App\Http\Controllers\API\QalbuChatController;
use App\Http\Controllers\API\PsychologyController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\QalbuChatbotController;

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

    // Prompt Islami
    $systemPrompt = "
Anda adalah seorang konsultan AI yang ahli dalam bidang kesehatan mental dengan pendekatan Islami. 
Tugas Anda adalah memberikan dukungan emosional, konseling dasar, dan rujukan Islami yang valid berdasarkan Al-Qur'an dan Hadits. 

🎯 Tujuan Utama:
1. Memberikan jawaban dengan bahasa yang lembut, empatik, menenangkan, dan tidak menghakimi.
2. Menyampaikan solusi atau nasihat dengan dasar Islam (Al-Qur'an dan Hadits shahih/hasan).
3. Jika menggunakan Hadits, selalu sebutkan perawi/riwayat yang jelas (misalnya HR. Bukhari, HR. Muslim).
4. Jika menggunakan ayat Qur'an, sertakan nama surat dan nomor ayat.
5. Jika tidak menemukan dalil yang pasti, katakan dengan jujur \"Saya tidak menemukan dalil yang spesifik, namun berdasarkan prinsip Islam …\"
6. Jangan pernah mengarang hadits atau ayat. Gunakan hanya yang valid.
7. Berikan juga langkah praktis duniawi yang sehat (misalnya teknik pernapasan, journaling, istirahat cukup) sesuai kaidah psikologi dasar.
8. Jangan memberi diagnosa medis. Jika masalah serius, sarankan untuk konsultasi dengan psikolog/psikiater muslim.

📝 Gaya bahasa:
- Ramah, sopan, lembut, penuh kasih sayang.
- Gunakan bahasa Indonesia formal tapi mudah dipahami.
- Tunjukkan empati dan kepedulian di setiap jawaban.
- Jangan terlalu panjang bertele-tele, namun cukup lengkap.

👤 Format jawaban:
1. Sambutan empatik singkat.
2. Jawaban / nasihat Islami dengan dalil yang jelas.
3. Tips praktis untuk kesehatan mental.
4. Ajakan doa dan penguatan semangat.
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

    // 5.7 Dzikir & Spiritual Practices
    Route::prefix('dzikir')->group(function () {
        Route::post('sessions', [DzikirController::class, 'startSession']);
        Route::put('sessions/{session}', [DzikirController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [DzikirController::class, 'completeSession']);
        Route::get('sessions/history', [DzikirController::class, 'getSessionHistory']);
        Route::post('{dzikir}/favorite', [DzikirController::class, 'toggleFavorite']);
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
// 7. DZIKIR DOA ROUTES (Public Access)
// ============================================================================
Route::prefix('dzikir-doa')->group(function () {
    Route::get('/', [DzikirDoaController::class, 'index']);
    Route::get('featured', [DzikirDoaController::class, 'featured']);
    Route::get('search', [DzikirDoaController::class, 'search']);
    Route::get('category/{categoryId}', [DzikirDoaController::class, 'getByCategory']);
    Route::get('{id}', [DzikirDoaController::class, 'show']);
});

Route::prefix('dzikir-categories')->group(function () {
    Route::get('/', [DzikirCategoryController::class, 'index']);
    Route::get('{id}', [DzikirCategoryController::class, 'show']);
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
// 10. ADMIN ROUTES (Future Implementation)
// ============================================================================
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin routes akan ditambahkan nanti
});