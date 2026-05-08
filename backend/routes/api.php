<?php

use App\Http\Controllers\Api\StudentAuthController;
use App\Http\Controllers\Api\StudentClearanceController;
use App\Http\Controllers\Api\StudentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [StudentAuthController::class, 'login'])->name('api.login');
Route::get('/registration/options', [StudentRegistrationController::class, 'options'])
    ->name('api.registration.options');
Route::post('/register', [StudentRegistrationController::class, 'store'])
    ->name('api.register');

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [StudentAuthController::class, 'me'])->name('api.me');

    Route::get('/clearance/current', [StudentClearanceController::class, 'current'])
        ->name('api.clearance.current');
    Route::post('/clearance', [StudentClearanceController::class, 'store'])
        ->name('api.clearance.store');
    Route::post('/clearance/steps/{step}/resubmit', [StudentClearanceController::class, 'resubmit'])
        ->name('api.clearance.steps.resubmit');
    Route::get('/clearance/current/pdf', [StudentClearanceController::class, 'downloadCurrent'])
        ->name('api.clearance.current.pdf');
    Route::get('/clearance/history/{clearance}/pdf', [StudentClearanceController::class, 'downloadHistory'])
        ->name('api.clearance.history.pdf');
});
