<?php

use App\Profiles\Central\Http\DashboardController;
use Illuminate\Support\Facades\Route;

// Routes that exist ONLY in the central profile.
Route::middleware(['auth', 'account.active', 'password.changed'])
    ->get('/', DashboardController::class)
    ->name('dashboard');
