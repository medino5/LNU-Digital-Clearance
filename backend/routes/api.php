<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClearanceController;
use App\Http\Controllers\ClearanceRequestController;

// 🔓 Public Routes (No token needed to access these)
Route::post('/login', [AuthController::class, 'login']);

// 🔒 Protected Routes (Flutter app MUST send a valid token to access these)
Route::middleware('auth:sanctum')->group(function () {

    // Anyone logged in can access these
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Students can create/view clearances.
    // NOTE: Each controller method (status/store/create) already checks $request->user()->is_student,
    // so we keep the student restriction there to avoid any unexpected middleware loops.
    Route::get('/clearance/status', [ClearanceRequestController::class, 'status']);
    Route::get('/clearance/history', [ClearanceRequestController::class, 'history']);
    Route::get('/clearance/history/{id}', [ClearanceRequestController::class, 'showHistoryDetail']);
    Route::post('/clearance', [ClearanceRequestController::class, 'store']);
    Route::delete('/clearance', [ClearanceRequestController::class, 'cancel']);
    Route::post('/clearance/create', [ClearanceController::class, 'create']);

    // 🛑 ONLY STAFF can approve clearances
    Route::middleware('staff')->group(function () {
        Route::post('/clearance/approve', [ClearanceController::class, 'approve']);
    });

});
