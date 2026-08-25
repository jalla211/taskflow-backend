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
Route::get('/login', function () {
    return response()->json(['message' => 'Please login to access this resource.'], 401);
})->name('login');