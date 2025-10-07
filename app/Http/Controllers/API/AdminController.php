<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Admin Login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Admin::where('email', $request->email)->first();

            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!$admin->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Admin account is deactivated'
                ], 403);
            }

            // Update last login
            $admin->update(['last_login_at' => now()]);

            // Create token
            $token = $admin->createToken('admin-auth-token', ['admin'])->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                        'permissions' => json_decode($admin->permissions, true),
                        'is_active' => $admin->is_active,
                        'last_login_at' => $admin->last_login_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Admin Profile
     */
    public function me(Request $request)
    {
        try {
            $admin = $request->user();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                        'permissions' => json_decode($admin->permissions, true),
                        'is_active' => $admin->is_active,
                        'last_login_at' => $admin->last_login_at,
                        'created_at' => $admin->created_at,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin Logout
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get All Admins (Super Admin only)
     */
    public function index(Request $request)
    {
        try {
            $admin = $request->user();

            // Check if user has permission to manage admins
            $permissions = json_decode($admin->permissions, true);
            if (!isset($permissions['manage_admins']) || !$permissions['manage_admins']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied'
                ], 403);
            }

            $admins = Admin::orderBy('created_at', 'desc')->get()->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'permissions' => json_decode($admin->permissions, true),
                    'is_active' => $admin->is_active,
                    'last_login_at' => $admin->last_login_at,
                    'created_at' => $admin->created_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'admins' => $admins
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get admins: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create New Admin (Super Admin only)
     */
    public function store(Request $request)
    {
        try {
            $admin = $request->user();

            // Check if user has permission to manage admins
            $permissions = json_decode($admin->permissions, true);
            if (!isset($permissions['manage_admins']) || !$permissions['manage_admins']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:super_admin,content_admin,moderator',
                'permissions' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newAdmin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'permissions' => json_encode($request->permissions),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Admin created successfully',
                'data' => [
                    'admin' => [
                        'id' => $newAdmin->id,
                        'name' => $newAdmin->name,
                        'email' => $newAdmin->email,
                        'role' => $newAdmin->role,
                        'permissions' => json_decode($newAdmin->permissions, true),
                        'is_active' => $newAdmin->is_active,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create admin: ' . $e->getMessage()
            ], 500);
        }
    }
}