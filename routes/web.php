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
// Run TaskSettingsSeeder to populate statuses & priorities
Route::get('/run-task-settings', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403, 'Invalid secret key.');
    }
    Artisan::call('db:seed', ['--class' => 'TaskSettingsSeeder', '--force' => true]);
    return Artisan::output();
});

// Create storage symlink for profile pictures
Route::get('/create-storage-link', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403);
    }
    $link = public_path('storage');
    $target = storage_path('app/public');

    if (is_link($link)) {
        return 'Symlink already exists.';
    }

    if (symlink($target, $link)) {
        return 'Storage symlink created successfully.';
    }

    return 'Failed to create symlink.';
});
Route::get('/check-data', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403);
    }
    $statuses = \App\Models\TaskStatus::all();
    $priorities = \App\Models\TaskPriority::all();
    return [
        'statuses' => $statuses,
        'priorities' => $priorities,
        'status_count' => $statuses->count(),
        'priority_count' => $priorities->count(),
    ];
});
Route::get('/insert-defaults', function () {
    $secret = 'migration2026';
    if (request('secret') !== $secret) {
        abort(403);
    }

    // Default statuses
    $statuses = [
        ['name' => 'To Do', 'slug' => 'todo', 'color' => '#6B7280', 'order' => 1, 'is_default' => true],
        ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#3B82F6', 'order' => 2, 'is_default' => false],
        ['name' => 'Review', 'slug' => 'review', 'color' => '#F59E0B', 'order' => 3, 'is_default' => false],
        ['name' => 'Testing', 'slug' => 'testing', 'color' => '#8B5CF6', 'order' => 4, 'is_default' => false],
        ['name' => 'Done', 'slug' => 'done', 'color' => '#10B981', 'order' => 5, 'is_default' => false],
    ];

    foreach ($statuses as $s) {
        \App\Models\TaskStatus::updateOrCreate(
            ['slug' => $s['slug']],
            $s
        );
    }

    // Default priorities
    $priorities = [
        ['name' => 'Low', 'slug' => 'low', 'color' => '#6B7280', 'level' => 1],
        ['name' => 'Medium', 'slug' => 'medium', 'color' => '#F59E0B', 'level' => 2],
        ['name' => 'High', 'slug' => 'high', 'color' => '#EF4444', 'level' => 3],
        ['name' => 'Critical', 'slug' => 'critical', 'color' => '#DC2626', 'level' => 4],
    ];

    foreach ($priorities as $p) {
        \App\Models\TaskPriority::updateOrCreate(
            ['slug' => $p['slug']],
            $p
        );
    }

    return [
        'message' => 'Default statuses and priorities inserted successfully.',
        'statuses' => \App\Models\TaskStatus::all(),
        'priorities' => \App\Models\TaskPriority::all(),
    ];
});
Route::get('/login', function () {
    return response()->json(['message' => 'Please login to access this resource.'], 401);
})->name('login');