<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuardianController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::prefix('auth')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.submit');

    Route::get('/doctor/login', [AuthController::class, 'showDoctorLogin'])->name('doctor.login');
    Route::post('/doctor/login', [AuthController::class, 'doctorLogin'])->name('doctor.login.submit');

    Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');

    Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'storeSignup'])->name('signup.store');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::get('/first-login-password', [AuthController::class, 'showFirstLoginPasswordForm'])->name('password.first.form')->middleware('auth');
    Route::post('/first-login-password', [AuthController::class, 'updateFirstLoginPassword'])->name('password.first.update')->middleware('auth');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

Route::prefix('doctor')->middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/dashboard', [DoctorController::class, 'dashboard'])->name('doctor.dashboard');
    Route::post('/settings', [DoctorController::class, 'updateSettings'])->name('doctor.settings.update');
    Route::post('/access/redeem', [DoctorController::class, 'redeemAccess'])
        ->middleware('throttle:doctor-access-redeem')
        ->name('doctor.access.redeem');
    Route::post('/access-sessions/{id}/end', [DoctorController::class, 'endSession'])->name('doctor.access.end');
    Route::get('/access-sessions/active', [DoctorController::class, 'activeSession'])->name('doctor.access.active');
    Route::get('/access-logs', [DoctorController::class, 'accessLogs'])->name('doctor.access.logs');
    Route::get('/access-sessions/{id}/telemetry', [DoctorController::class, 'sessionTelemetry'])->name('doctor.access.telemetry');
    Route::get('/access-sessions/{id}/report.pdf', [DoctorController::class, 'sessionReportPdf'])->name('doctor.access.report');
});

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/professionals', [AdminController::class, 'getProfessionals']);
    Route::post('/professionals', [AdminController::class, 'addProfessional']);
    Route::put('/professionals/{id}', [AdminController::class, 'editProfessional']);
    Route::delete('/professionals/{id}', [AdminController::class, 'deleteProfessional']);
    Route::post('/professionals/{id}/toggle-verification', [AdminController::class, 'toggleVerification']);
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

    Route::post('/settings', [AdminController::class, 'updateSettings']);
    Route::get('/stats', [AdminController::class, 'getStats']);
});

Route::prefix('guardian')->middleware(['auth', 'role:guardian'])->group(function () {
    Route::get('/dashboard', [GuardianController::class, 'useMobile'])->name('guardian.dashboard');
    Route::get('/children', [GuardianController::class, 'useMobile'])->name('guardian.children');
});
