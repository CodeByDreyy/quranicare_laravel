<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MoodController;
use App\Http\Controllers\API\BreathingController;
use App\Http\Controllers\API\AudioController;
use App\Http\Controllers\API\QuranController;
use App\Http\Controllers\API\JournalController;
use App\Http\Controllers\API\DzikirController;
use App\Http\Controllers\API\QalbuChatController;
use App\Http\Controllers\API\PsychologyController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Public content routes (for guest users)
Route::prefix('public')->group(function () {
    Route::get('quran/surahs', [QuranController::class, 'getSurahs']);
    Route::get('quran/surahs/{surah}/ayahs', [QuranController::class, 'getAyahs']);
    Route::get('dzikir/categories', [DzikirController::class, 'getCategories']);
    Route::get('dzikir/{category}/items', [DzikirController::class, 'getDzikirByCategory']);
    Route::get('psychology/categories', [PsychologyController::class, 'getCategories']);
    Route::get('psychology/{category}/materials', [PsychologyController::class, 'getMaterials']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // User profile routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'getProfile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('profile/picture', [UserController::class, 'updateProfilePicture']);
        Route::get('dashboard', [UserController::class, 'getDashboard']);
        Route::get('statistics', [UserController::class, 'getStatistics']);
    });

    // Mood tracking routes
    Route::prefix('mood')->group(function () {
        Route::get('/', [MoodController::class, 'index']);
        Route::post('/', [MoodController::class, 'store']);
        Route::get('today', [MoodController::class, 'getTodayMoods']);
        Route::get('statistics', [MoodController::class, 'getStatistics']);
        Route::get('history', [MoodController::class, 'getHistory']);
        Route::put('{mood}', [MoodController::class, 'update']);
        Route::delete('{mood}', [MoodController::class, 'destroy']);
    });

    // Breathing exercise routes
    Route::prefix('breathing')->group(function () {
        Route::get('categories', [BreathingController::class, 'getCategories']);
        Route::get('exercises', [BreathingController::class, 'getExercises']);
        Route::get('exercises/{exercise}', [BreathingController::class, 'getExercise']);
        Route::post('sessions', [BreathingController::class, 'startSession']);
        Route::put('sessions/{session}', [BreathingController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [BreathingController::class, 'completeSession']);
        Route::get('sessions/history', [BreathingController::class, 'getSessionHistory']);
    });

    // Audio relax routes
    Route::prefix('audio')->group(function () {
        Route::get('categories', [AudioController::class, 'getCategories']);
        Route::get('/', [AudioController::class, 'index']);
        Route::get('{audio}', [AudioController::class, 'show']);
        Route::post('{audio}/play', [AudioController::class, 'recordPlay']);
        Route::post('sessions', [AudioController::class, 'startSession']);
        Route::put('sessions/{session}', [AudioController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [AudioController::class, 'completeSession']);
        Route::post('{audio}/rate', [AudioController::class, 'rateAudio']);
    });

    // Quran routes
    Route::prefix('quran')->group(function () {
        Route::get('surahs', [QuranController::class, 'getSurahs']);
        Route::get('surahs/{surah}', [QuranController::class, 'getSurah']);
        Route::get('surahs/{surah}/ayahs', [QuranController::class, 'getAyahs']);
        Route::get('ayahs/{ayah}', [QuranController::class, 'getAyah']);
        Route::get('search', [QuranController::class, 'search']);
        Route::post('ayahs/{ayah}/bookmark', [QuranController::class, 'toggleBookmark']);
    });

    // Journal routes
    Route::prefix('journal')->group(function () {
        Route::get('/', [JournalController::class, 'index']);
        Route::post('/', [JournalController::class, 'store']);
        Route::get('{journal}', [JournalController::class, 'show']);
        Route::put('{journal}', [JournalController::class, 'update']);
        Route::delete('{journal}', [JournalController::class, 'destroy']);
        Route::get('tags/suggestions', [JournalController::class, 'getTagSuggestions']);
        Route::post('{journal}/favorite', [JournalController::class, 'toggleFavorite']);
    });

    // Dzikir routes
    Route::prefix('dzikir')->group(function () {
        Route::get('categories', [DzikirController::class, 'getCategories']);
        Route::get('/', [DzikirController::class, 'index']);
        Route::get('{dzikir}', [DzikirController::class, 'show']);
        Route::post('sessions', [DzikirController::class, 'startSession']);
        Route::put('sessions/{session}', [DzikirController::class, 'updateSession']);
        Route::post('sessions/{session}/complete', [DzikirController::class, 'completeSession']);
        Route::get('sessions/history', [DzikirController::class, 'getSessionHistory']);
        Route::post('{dzikir}/favorite', [DzikirController::class, 'toggleFavorite']);
    });

    // Qalbu Chat (AI) routes
    Route::prefix('qalbu')->group(function () {
        Route::get('conversations', [QalbuChatController::class, 'getConversations']);
        Route::post('conversations', [QalbuChatController::class, 'createConversation']);
        Route::get('conversations/{conversation}', [QalbuChatController::class, 'getConversation']);
        Route::post('conversations/{conversation}/messages', [QalbuChatController::class, 'sendMessage']);
        Route::put('conversations/{conversation}', [QalbuChatController::class, 'updateConversation']);
        Route::delete('conversations/{conversation}', [QalbuChatController::class, 'deleteConversation']);
        Route::post('messages/{message}/feedback', [QalbuChatController::class, 'provideFeedback']);
    });

    // Psychology learning routes
    Route::prefix('psychology')->group(function () {
        Route::get('categories', [PsychologyController::class, 'getCategories']);
        Route::get('materials', [PsychologyController::class, 'getMaterials']);
        Route::get('materials/{material}', [PsychologyController::class, 'getMaterial']);
        Route::post('materials/{material}/progress', [PsychologyController::class, 'updateProgress']);
        Route::post('materials/{material}/bookmark', [PsychologyController::class, 'toggleBookmark']);
        Route::post('materials/{material}/rate', [PsychologyController::class, 'rateMaterial']);
        Route::get('progress', [PsychologyController::class, 'getProgress']);
    });

    // Favorites routes
    Route::prefix('favorites')->group(function () {
        Route::get('/', [UserController::class, 'getFavorites']);
        Route::post('/', [UserController::class, 'addToFavorites']);
        Route::delete('{favorite}', [UserController::class, 'removeFromFavorites']);
    });

    // Notifications routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [UserController::class, 'getNotifications']);
        Route::post('{notification}/read', [UserController::class, 'markNotificationAsRead']);
        Route::post('mark-all-read', [UserController::class, 'markAllNotificationsAsRead']);
    });
});

// Admin routes (if needed)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin routes will be added here
});