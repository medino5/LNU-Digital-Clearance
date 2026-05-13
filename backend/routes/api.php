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
Route::post('/forgot-password', [StudentAuthController::class, 'resetForgottenPassword'])
    ->name('api.forgot-password.reset');

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [StudentAuthController::class, 'me'])->name('api.me');
    Route::post('/me/password', [StudentAuthController::class, 'updatePassword'])
        ->name('api.me.password.update');
    Route::patch('/me/academic-profile', [StudentAuthController::class, 'updateAcademicProfile'])
        ->name('api.me.academic-profile.update');
    Route::post('/me/profile-photo', [StudentAuthController::class, 'updateProfilePhoto'])
        ->name('api.me.profile-photo.update');

    Route::get('/clearance/current', [StudentClearanceController::class, 'current'])
        ->name('api.clearance.current');
    Route::get('/clearance/history', [StudentClearanceController::class, 'history'])
        ->name('api.clearance.history');
    Route::post('/clearance', [StudentClearanceController::class, 'store'])
        ->name('api.clearance.store');
    Route::post('/clearance/steps/{step}/resubmit', [StudentClearanceController::class, 'resubmit'])
        ->name('api.clearance.steps.resubmit');
    Route::get('/clearance/current/pdf', [StudentClearanceController::class, 'downloadCurrent'])
        ->name('api.clearance.current.pdf');
    Route::get('/clearance/history/{clearance}/pdf', [StudentClearanceController::class, 'downloadHistory'])
        ->name('api.clearance.history.pdf');
});
