<?php
use App\Http\Controllers\ClearanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/clearance/create', [ClearanceController::class, 'create']);
Route::post('/clearance/approve', [ClearanceController::class, 'approve']);
