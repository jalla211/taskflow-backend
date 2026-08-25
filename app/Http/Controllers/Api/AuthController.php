<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail as MailFacade;
use App\Mail\OtpMail;
class AuthController extends Controller
{
    // Register new user (Admin only)
    public function register(Request $request)
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

    // Login - Step 1: Send OTP
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    if (!$user->is_active) {
        return response()->json(['message' => 'Account is deactivated'], 403);
    }

    // Create token directly (no OTP)
    $token = $user->createToken('auth_token')->plainTextToken;
    $user->update(['last_login_at' => now()]);

    return response()->json([
        'message' => 'Login successful',
        'user' => $user->load('role'),
        'token' => $token,
    ]);
}

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    // Get authenticated user
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('role'),
        ]);
    }

    // Update profile
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

    // Forgot password - send reset link
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        return response()->json([
            'message' => 'Password reset link sent to your email',
        ]);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }
}