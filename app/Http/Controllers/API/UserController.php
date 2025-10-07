<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Mood;
use App\Models\Journal;
use App\Models\UserActivityLog;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function getProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'birth_date' => $user->birth_date,
                    'gender' => $user->gender,
                    'phone' => $user->phone,
                    'profile_picture' => $user->profile_picture,
                    'bio' => $user->bio,
                    'preferred_language' => $user->preferred_language,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            ]);

            $user->update($request->only(['name', 'email']));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update profile picture
     */
    public function updateProfilePicture(Request $request)
    {
        try {
            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $user = Auth::user();
            
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            
            $user->update(['profile_picture' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'data' => [
                    'profile_picture' => $path,
                    'profile_picture_url' => Storage::url($path)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user dashboard data
     */
    public function getDashboard(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // Get today's mood
            $todayMood = Mood::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->latest()
                ->first();

            // Get recent journal entries
            $recentJournals = Journal::where('user_id', $user->id)
                ->latest()
                ->limit(3)
                ->get();

            // Get activity stats for the week
            $weekStart = Carbon::now()->startOfWeek();
            $weeklyActivities = UserActivityLog::where('user_id', $user->id)
                ->where('created_at', '>=', $weekStart)
                ->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'profile_picture' => $user->profile_picture
                    ],
                    'today_mood' => $todayMood,
                    'recent_journals' => $recentJournals,
                    'weekly_activities' => $weeklyActivities,
                    'stats' => [
                        'total_journals' => Journal::where('user_id', $user->id)->count(),
                        'total_moods' => Mood::where('user_id', $user->id)->count(),
                        'streak_days' => $this->calculateStreak($user->id),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total_moods' => Mood::where('user_id', $user->id)->count(),
                'total_journals' => Journal::where('user_id', $user->id)->count(),
                'total_activities' => UserActivityLog::where('user_id', $user->id)->count(),
                'streak_days' => $this->calculateStreak($user->id),
                'this_month_moods' => Mood::where('user_id', $user->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->count(),
                'this_month_journals' => Journal::where('user_id', $user->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user favorites
     */
    public function getFavorites(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get user favorites from UserFavorite model
            $favorites = $user->favorites()->with(['favoritable'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Favorites retrieved successfully',
                'data' => $favorites
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user notifications
     */
    public function getNotifications(Request $request)
    {
        try {
            $user = Auth::user();
            
            $notifications = $user->notifications()
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate user streak
     */
    private function calculateStreak($userId)
    {
        $activities = UserActivityLog::where('user_id', $userId)
            ->selectRaw('DATE(created_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $streak = 0;
        $currentDate = Carbon::yesterday(); // Start from yesterday

        foreach ($activities as $activity) {
            $activityDate = Carbon::parse($activity->date);
            
            if ($activityDate->equalTo($currentDate)) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Refresh user profile (force reload from database)
     */
    public function refreshProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Force refresh from database
            $user->refresh();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile refreshed successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'birth_date' => $user->birth_date,
                    'gender' => $user->gender,
                    'phone' => $user->phone,
                    'profile_picture' => $user->profile_picture,
                    'bio' => $user->bio,
                    'preferred_language' => $user->preferred_language,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug user info
     */
    public function debugUser(Request $request)
    {
        try {
            $user = Auth::user();
            $token = $request->bearerToken();
            
            return response()->json([
                'success' => true,
                'message' => 'Debug info retrieved',
                'data' => [
                    'authenticated_user' => $user,
                    'token_info' => $token ? 'Token present' : 'No token',
                    'timestamp' => Carbon::now(),
                    'debug_info' => [
                        'guard' => Auth::getDefaultDriver(),
                        'user_id' => Auth::id(),
                        'user_from_db' => User::find(Auth::id()),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get greeting message with user name
     */
    public function getGreeting(Request $request)
    {
        try {
            $user = Auth::user();
            $hour = Carbon::now()->hour;
            
            // Determine greeting based on time
            if ($hour < 11) {
                $greeting = 'بسم الله الرحمن الرحيم'; // Bismillah
                $greeting_text = 'Assalamualaikum';
            } elseif ($hour < 15) {
                $greeting = 'بسم الله الرحمن الرحيم';
                $greeting_text = 'Assalamualaikum';
            } elseif ($hour < 18) {
                $greeting = 'بسم الله الرحمن الرحيم';
                $greeting_text = 'Assalamualaikum';
            } else {
                $greeting = 'بسم الله الرحمن الرحيم';
                $greeting_text = 'Assalamualaikum';
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Greeting retrieved successfully',
                'data' => [
                    'arabic_greeting' => $greeting,
                    'greeting' => $greeting_text,
                    'user_name' => $user->name,
                    'full_greeting' => $greeting_text . ' ' . $user->name,
                    'current_time' => Carbon::now()->format('H:i'),
                    'current_date' => Carbon::now()->format('Y-m-d'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get greeting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
