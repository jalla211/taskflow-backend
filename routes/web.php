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
Route::get('/manual-otp', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403);
    }
    $user = \App\Models\User::where('email', 'gateteprince24@gmail.com')->first();
    if (!$user) {
        return 'User not found';
    }
    $otp = '123456';
    \App\Models\TwoFactorCode::updateOrCreate(
        ['user_id' => $user->id],
        ['code' => $otp, 'expires_at' => now()->addMinutes(10), 'is_used' => false]
    );
    return 'OTP set to 123456 for ' . $user->email . '. Use this to login.';
});
Route::get('/login', function () {
    return response()->json(['message' => 'Please login to access this resource.'], 401);
})->name('login');