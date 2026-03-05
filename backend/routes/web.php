<?php

use App\Http\Controllers\ClearanceController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffDashboardController;
use Illuminate\Support\Facades\Route;

// Existing welcome route
Route::get('/', function () {
    return view('welcome');
});

// =========================
// Staff Login Routes
// =========================
Route::get('/staff/login', [StaffAuthController::class, 'showLogin'])->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'login'])->name('staff.login.submit');

// =========================
// Staff Dashboard (web guard)
// =========================
Route::middleware('auth')->group(function () {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])
        ->name('staff.dashboard');
    Route::post('/staff/process/{signature}', [StaffDashboardController::class, 'processSignature'])
        ->name('staff.process');
});