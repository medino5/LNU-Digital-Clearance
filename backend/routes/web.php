<?php

use App\Http\Controllers\ClearanceController;
use App\Http\Controllers\StaffAuthController;
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
// Placeholder Dashboard for Staff
// =========================
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');