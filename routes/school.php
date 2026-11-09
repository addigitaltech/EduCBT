<?php

use App\Profiles\School\Http\DashboardController;
use Illuminate\Support\Facades\Route;

// Routes that exist ONLY in the school profile.
Route::middleware(['auth', 'account.active', 'password.changed'])
    ->get('/', DashboardController::class)
    ->name('dashboard');
