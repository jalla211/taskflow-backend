<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/run-seeders', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403, 'Invalid secret key.');
    }
    Artisan::call('db:seed', ['--class' => 'DefaultUsersSeeder', '--force' => true]);
    return Artisan::output();
});

// Temporary route to run migrations after deployment
Route::get('/run-migrations', function () {
    $secret = 'migration2026'; // Change to a strong secret later
    if (request('secret') !== $secret) {
        abort(403, 'Invalid secret key.');
    }
    Artisan::call('migrate', ['--force' => true]);
    return Artisan::output();
});
Route::get('/force-otp', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403);
    }
    $user = \App\Models\User::where('email', 'gateteprince24@gmail.com')->first();
    if (!$user) {
        return 'User not found';
    }
    // Delete old OTPs
    \App\Models\TwoFactorCode::where('user_id', $user->id)->delete();
    // Create new OTP
    \App\Models\TwoFactorCode::create([
        'user_id' => $user->id,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'is_used' => false,
    ]);
    return 'OTP set to 123456 for ' . $user->email . '. Use this to login.';
});
Route::get('/view-otp', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403);
    }
    $user = \App\Models\User::where('email', 'gateteprince24@gmail.com')->first();
    if (!$user) {
        return 'User not found';
    }
    $otpRecord = \App\Models\TwoFactorCode::where('user_id', $user->id)->first();
    if (!$otpRecord) {
        return 'No OTP record found for this user.';
    }
    return [
        'otp' => $otpRecord->code,
        'expires_at' => $otpRecord->expires_at,
        'is_used' => $otpRecord->is_used,
        'created_at' => $otpRecord->created_at,
    ];
});
Route::get('/direct-login', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403, 'Invalid secret');
    }
    $user = \App\Models\User::where('email', 'gateteprince24@gmail.com')->first();
    if (!$user) {
        return 'User not found. Please run seeders first.';
    }
    $token = $user->createToken('auth_token')->plainTextToken;
    return [
        'token' => $token,
        'user' => $user->load('role'),
        'message' => 'Logged in successfully! Copy the token to local storage.'
    ];
});
Route::get('/login', function () {
    return response()->json(['message' => 'Please login to access this resource.'], 401);
})->name('login');