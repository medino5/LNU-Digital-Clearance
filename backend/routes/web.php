<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminClearanceDetailController;
use App\Http\Controllers\AdminClearanceReportController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminStudentRegistrationRequestController;
use App\Http\Controllers\AdminOfficeDesignationController;
use App\Http\Controllers\OfficeAccountAdminController;
use App\Http\Controllers\OfficeDashboardController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ProgramAdminController;
use App\Http\Controllers\SemesterAdminController;
use App\Http\Controllers\StudentAdminController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalAuthController::class, 'landing'])->name('portal.landing');
Route::get('/profile-photos/{fileName}', [ProfilePhotoController::class, 'show'])
    ->name('profile-photos.show');

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
        Route::get('/programs', [ProgramAdminController::class, 'index'])->name('admin.programs.index');
        Route::get('/semesters', [SemesterAdminController::class, 'index'])->name('admin.semesters.index');
        Route::get('/routing', [AdminOfficeDesignationController::class, 'index'])->name('admin.routing.index');
        Route::get('/students', [StudentAdminController::class, 'index'])->name('admin.students.index');
        Route::get('/registration-requests', [AdminStudentRegistrationRequestController::class, 'index'])
            ->name('admin.registration-requests.index');
        Route::get('/office-accounts', [OfficeAccountAdminController::class, 'index'])->name('admin.office-accounts.index');
        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('admin.analytics.index');
        Route::get('/analytics/export', [AdminAnalyticsController::class, 'export'])->name('admin.analytics.export');
        Route::get('/clearance-history', [AdminClearanceReportController::class, 'index'])->name('admin.clearance-history.index');
        Route::get('/students/{student}', [StudentProfileController::class, 'adminShow'])->name('admin.students.show');
        Route::get('/clearances/{clearance}', [AdminClearanceDetailController::class, 'show'])
            ->name('admin.clearances.show');
        Route::post('/clearance-reports/completed', [AdminClearanceReportController::class, 'export'])
            ->name('admin.clearance-reports.completed.export');

        Route::post('/programs', [ProgramAdminController::class, 'store'])->name('admin.programs.store');
        Route::put('/programs/{program}', [ProgramAdminController::class, 'update'])->name('admin.programs.update');
        Route::delete('/programs/{program}', [ProgramAdminController::class, 'destroy'])
            ->missing(fn () => redirect()
                ->route('admin.programs.index')
                ->with('info', 'Program was already deleted or no longer exists.'))
            ->name('admin.programs.destroy');

        Route::post('/semesters', [SemesterAdminController::class, 'store'])->name('admin.semesters.store');
        Route::put('/semesters/{semester}', [SemesterAdminController::class, 'update'])->name('admin.semesters.update');
        Route::delete('/semesters/{semester}', [SemesterAdminController::class, 'destroy'])
            ->missing(fn () => redirect()
                ->route('admin.semesters.index')
                ->with('info', 'Semester was already deleted or no longer exists.'))
            ->name('admin.semesters.destroy');

        Route::post('/students', [StudentAdminController::class, 'store'])->name('admin.students.store');
        Route::put('/students/{student}', [StudentAdminController::class, 'update'])->name('admin.students.update');
        Route::delete('/students/{student}', [StudentAdminController::class, 'destroy'])->name('admin.students.destroy');
        Route::post('/registration-requests/{registrationRequest}/approve', [AdminStudentRegistrationRequestController::class, 'approve'])
            ->name('admin.registration-requests.approve');
        Route::post('/registration-requests/{registrationRequest}/reject', [AdminStudentRegistrationRequestController::class, 'reject'])
            ->name('admin.registration-requests.reject');

        Route::post('/office-accounts', [OfficeAccountAdminController::class, 'store'])->name('admin.office-accounts.store');
        Route::put('/office-accounts/{officeAccount}', [OfficeAccountAdminController::class, 'update'])
            ->name('admin.office-accounts.update');
        Route::post('/office-accounts/{officeAccount}/profile-photo', [OfficeAccountAdminController::class, 'updateProfilePhoto'])
            ->name('admin.office-accounts.profile-photo.update');

        Route::get('/office-designations/{officeDesignation}/eligible-users', [AdminOfficeDesignationController::class, 'eligibleUsers'])
            ->name('admin.office-designations.eligible-users');
        Route::put('/office-designations/{officeDesignation}/assignment', [AdminOfficeDesignationController::class, 'updateAssignment'])
            ->name('admin.office-designations.assignment.update');
    });

Route::prefix('office')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [OfficeDashboardController::class, 'index'])->name('office.dashboard');
        Route::get('/students/{student}', [StudentProfileController::class, 'officeShow'])->name('office.students.show');
        Route::post('/steps/{step}/process', [OfficeDashboardController::class, 'process'])->name('office.steps.process');
    });
