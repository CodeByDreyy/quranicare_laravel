<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DzikirDoa;
use App\Models\AudioRelax;
use App\Models\PsychologyMaterial;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        try {
            // Get statistics
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'new_this_month' => User::whereMonth('created_at', Carbon::now()->month)->count(),
                ],
                'content' => [
                    'dzikir_doa' => DzikirDoa::count(),
                    'audio_relax' => AudioRelax::count(),
                    'psychology_materials' => PsychologyMaterial::count(),
                    'notifications' => Notification::count(),
                ],
                'activity' => [
                    'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
                    'recent_content' => [
                        'dzikir' => DzikirDoa::latest()->take(3)->get(['id', 'title', 'created_at']),
                        'audio' => AudioRelax::latest()->take(3)->get(['id', 'title', 'created_at']),
                        'psychology' => PsychologyMaterial::latest()->take(3)->get(['id', 'title', 'created_at']),
                    ]
                ]
            ];

            return response()->json([
                'message' => 'Dashboard data retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSystemInfo()
    {
        try {
            $systemInfo = [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => config('database.default'),
                'timezone' => config('app.timezone'),
                'environment' => app()->environment(),
                'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'message' => 'System information retrieved successfully',
                'data' => $systemInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve system information',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
