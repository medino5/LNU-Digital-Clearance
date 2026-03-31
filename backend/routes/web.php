<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminClearanceDetailController;
use App\Http\Controllers\AdminOfficeDesignationController;
use App\Http\Controllers\OfficeAccountAdminController;
use App\Http\Controllers\OfficeDashboardController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\ProgramAdminController;
use App\Http\Controllers\SemesterAdminController;
use App\Http\Controllers\StudentAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalAuthController::class, 'landing'])->name('portal.landing');

Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('portal.login');
Route::post('/login', [PortalAuthController::class, 'login'])->name('portal.login.submit');

Route::redirect('/admin/login', '/login')->name('admin.login');
Route::post('/admin/login', [PortalAuthController::class, 'login'])->name('admin.login.submit');

Route::redirect('/office/login', '/login')->name('office.login');
Route::post('/office/login', [PortalAuthController::class, 'login'])->name('office.login.submit');

Route::post('/logout', [PortalAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('portal.logout');

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/clearances/{clearance}', [AdminClearanceDetailController::class, 'show'])
            ->name('admin.clearances.show');

        Route::post('/programs', [ProgramAdminController::class, 'store'])->name('admin.programs.store');
        Route::put('/programs/{program}', [ProgramAdminController::class, 'update'])->name('admin.programs.update');

        Route::post('/semesters', [SemesterAdminController::class, 'store'])->name('admin.semesters.store');
        Route::put('/semesters/{semester}', [SemesterAdminController::class, 'update'])->name('admin.semesters.update');

        Route::post('/students', [StudentAdminController::class, 'store'])->name('admin.students.store');
        Route::put('/students/{student}', [StudentAdminController::class, 'update'])->name('admin.students.update');

        Route::post('/office-accounts', [OfficeAccountAdminController::class, 'store'])->name('admin.office-accounts.store');
        Route::put('/office-accounts/{officeAccount}', [OfficeAccountAdminController::class, 'update'])
            ->name('admin.office-accounts.update');

        Route::put('/office-designations/{officeDesignation}/assignment', [AdminOfficeDesignationController::class, 'updateAssignment'])
            ->name('admin.office-designations.assignment.update');
    });

Route::prefix('office')
    ->middleware(['auth', 'role:office'])
    ->group(function () {
        Route::get('/', [OfficeDashboardController::class, 'index'])->name('office.dashboard');
        Route::post('/steps/{step}/process', [OfficeDashboardController::class, 'process'])->name('office.steps.process');
    });
