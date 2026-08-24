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

    // Generate OTP
    $otp = rand(100000, 999999);

    TwoFactorCode::updateOrCreate(
        ['user_id' => $user->id],
        [
            'code' => $otp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]
    );

    // --- SEND REAL EMAIL ---
    try {
       MailFacade::to($user->email)->send(new OtpMail($otp, $user->name));
    } catch (\Exception $e) {
        // Log error but don't break login flow
        \Log::error('Failed to send OTP email: ' . $e->getMessage());
    }

    // Return response (hide OTP in production)
    $response = [
        'message' => 'OTP sent to your email',
        'user_id' => $user->id,
    ];

    // For development only: include OTP for testing
    if (env('APP_ENV') !== 'production') {
        $response['otp'] = $otp;
    }

    return response()->json($response);
}

    // Verify OTP and login
public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required_without:user_id|email|exists:users,email',
        'user_id' => 'required_without:email|exists:users,id',
        'code' => 'required_without:otp|string|size:6',
        'otp' => 'required_without:code|string|size:6',
    ]);

    // Find user by either email or user_id
    if ($request->has('email')) {
        $user = User::where('email', $request->email)->first();
    } else {
        $user = User::find($request->user_id);
    }

    if (!$user) {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    // Get the OTP code from either field
    $otpCode = $request->code ?? $request->otp;

    $twoFactor = TwoFactorCode::where('user_id', $user->id)
        ->where('code', $otpCode)
        ->where('is_used', false)
        ->first();

    if (!$twoFactor) {
        return response()->json([
            'message' => 'Invalid OTP',
        ], 401);
    }

    if ($twoFactor->expires_at < now()) {
        return response()->json([
            'message' => 'OTP has expired',
        ], 401);
    }

    $twoFactor->update(['is_used' => true]);

    $user->update(['last_login_at' => now()]);

    $token = $user->createToken('auth_token')->plainTextToken;

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