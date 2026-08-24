<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Get all users (Admin only)
    public function index(Request $request)
    {
        $users = User::with('role')->get();
        return response()->json($users);
    }

    // Create new user (Admin only)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('role'),
        ], 201);
    }

    // Get single user (Admin only)
    public function show($id)
    {
        $user = User::with('role')->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    // Update user (Admin only)
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'sometimes|exists:roles,id',
            'phone' => 'sometimes|nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($request->all());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('role'),
        ]);
    }

    // Deactivate user (Admin only)
    public function deactivate($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'User deactivated successfully',
            'user' => $user->load('role'),
        ]);
    }

    // Reactivate user (Admin only)
    public function reactivate($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'User reactivated successfully',
            'user' => $user->load('role'),
        ]);
    }

    // Get all roles (Admin only)
    public function roles()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    // Update own profile (Any user)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'profile_picture' => 'sometimes|nullable|string',
        ]);

        $user->update($request->only(['name', 'phone', 'profile_picture']));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()->load('role'),
        ]);
    }
// Update password
public function updatePassword(Request $request)
{
    $user = $request->user();

    $request->validate([
        'current_password' => 'required|string',
        'password' => 'required|string|min:8|confirmed',
    ]);

    // Check current password
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'message' => 'Current password is incorrect',
        ], 400);
    }

    $user->update([
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'message' => 'Password updated successfully',
    ]);
}// Upload profile picture
public function uploadProfilePicture(Request $request)
{
    $user = $request->user();

    $request->validate([
        'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // Delete old profile picture if exists
    if ($user->profile_picture) {
        Storage::disk('public')->delete($user->profile_picture);
    }

    $file = $request->file('profile_picture');
    $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
    $path = $file->storeAs('profile_pictures', $filename, 'public');

    $user->update([
        'profile_picture' => $path,
    ]);

    return response()->json([
        'message' => 'Profile picture uploaded successfully',
        'profile_picture' => $path,
    ]);
}

}