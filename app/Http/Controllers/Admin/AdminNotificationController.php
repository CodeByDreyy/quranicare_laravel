<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Notification::with('user:id,name,email');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('is_read')) {
                $query->where('is_read', $request->is_read);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                                  ->paginate($request->get('per_page', 15));

            return response()->json([
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve notifications',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:mood_reminder,dzikir_reminder,breathing_reminder,new_content,achievement',
            'data' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $notifications = [];
            $data = [
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'data' => json_encode($request->data),
                'scheduled_at' => $request->scheduled_at,
            ];

            foreach ($request->user_ids as $userId) {
                $notifications[] = array_merge($data, [
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Notification::insert($notifications);

            return response()->json([
                'message' => 'Notifications created successfully',
                'count' => count($notifications)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create notifications',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function broadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:mood_reminder,dzikir_reminder,breathing_reminder,new_content,achievement',
            'data' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'target_criteria' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $userQuery = User::where('is_active', true);

            // Apply targeting criteria if provided
            if ($request->has('target_criteria')) {
                $criteria = $request->target_criteria;
                
                if (isset($criteria['gender'])) {
                    $userQuery->where('gender', $criteria['gender']);
                }
                
                if (isset($criteria['age_min']) || isset($criteria['age_max'])) {
                    $userQuery->whereNotNull('birth_date');
                    
                    if (isset($criteria['age_min'])) {
                        $userQuery->whereDate('birth_date', '<=', now()->subYears($criteria['age_min']));
                    }
                    
                    if (isset($criteria['age_max'])) {
                        $userQuery->whereDate('birth_date', '>=', now()->subYears($criteria['age_max']));
                    }
                }
            }

            $users = $userQuery->pluck('id');

            if ($users->isEmpty()) {
                return response()->json([
                    'error' => 'No users match the targeting criteria'
                ], 422);
            }

            $notifications = [];
            $data = [
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'data' => json_encode($request->data),
                'scheduled_at' => $request->scheduled_at,
            ];

            foreach ($users as $userId) {
                $notifications[] = array_merge($data, [
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Notification::insert($notifications);

            return response()->json([
                'message' => 'Broadcast notifications created successfully',
                'count' => count($notifications),
                'targeted_users' => $users->count()
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to broadcast notifications',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $notification = Notification::with('user:id,name,email')->findOrFail($id);

            return response()->json([
                'message' => 'Notification retrieved successfully',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Notification not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:mood_reminder,dzikir_reminder,breathing_reminder,new_content,achievement',
            'data' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $notification = Notification::findOrFail($id);
            
            $data = $request->all();
            $data['data'] = json_encode($request->data);

            $notification->update($data);

            return response()->json([
                'message' => 'Notification updated successfully',
                'data' => $notification->load('user:id,name,email')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update notification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete notification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total' => Notification::count(),
                'unread' => Notification::where('is_read', false)->count(),
                'read' => Notification::where('is_read', true)->count(),
                'scheduled' => Notification::where('scheduled_at', '>', now())->count(),
                'by_type' => Notification::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'recent' => Notification::with('user:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'user_id', 'title', 'type', 'is_read', 'created_at'])
            ];

            return response()->json([
                'message' => 'Notification stats retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve notification stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsers(Request $request)
    {
        try {
            $query = User::where('is_active', true);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->select('id', 'name', 'email', 'created_at')
                          ->orderBy('name')
                          ->paginate($request->get('per_page', 20));

            return response()->json([
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve users',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
