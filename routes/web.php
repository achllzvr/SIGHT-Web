<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuardianController;

// Welcome page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Unified Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.submit');

    // Doctor Login (legacy)
    Route::get('/doctor/login', [AuthController::class, 'showDoctorLogin'])->name('doctor.login');
    Route::post('/doctor/login', [AuthController::class, 'doctorLogin'])->name('doctor.login.submit');

    // Admin Login (legacy)
    Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.submit');

    // Signup
    Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'storeSignup'])->name('signup.store');

    // Forgot/Reset Password
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::get('/first-login-password', [AuthController::class, 'showFirstLoginPasswordForm'])->name('password.first.form')->middleware('auth');
    Route::post('/first-login-password', [AuthController::class, 'updateFirstLoginPassword'])->name('password.first.update')->middleware('auth');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

// Doctor Routes
Route::prefix('doctor')->middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/dashboard', [DoctorController::class, 'dashboard'])->name('doctor.dashboard');
    Route::get('/patient/{patientId}', [DoctorController::class, 'getPatient']);
    Route::put('/requests/{link_id}', [\App\Http\Controllers\DoctorController::class, 'respondToRequest']);
    Route::get('/patient/{patientId}/compliance', [DoctorController::class, 'getComplianceData']);
    Route::get('/patient/{patientId}/activity', [DoctorController::class, 'getActivityLog']);
    Route::post('/patient/{patientId}/health-plan', [App\Http\Controllers\DoctorController::class, 'sendHealthPlan'])->name('doctor.send_plan');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // Professionals Management
    Route::get('/professionals', [AdminController::class, 'getProfessionals']);
    Route::post('/professionals', [AdminController::class, 'addProfessional']);
    Route::put('/professionals/{id}', [AdminController::class, 'editProfessional']);
    Route::delete('/professionals/{id}', [AdminController::class, 'deleteProfessional']);
    Route::post('/professionals/{id}/toggle-verification', [AdminController::class, 'toggleVerification']);
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

    // Settings
    Route::post('/settings', [AdminController::class, 'updateSettings']);
    Route::post('/admin/add', [AdminController::class, 'addAdmin']);
    Route::delete('/admin/{id}', [AdminController::class, 'removeAdmin']);

    // Stats
    Route::get('/stats', [AdminController::class, 'getStats']);
});

// Guardian Routes
Route::prefix('guardian')->middleware(['auth', 'role:guardian'])->group(function () {
    Route::get('/children', [GuardianController::class, 'children'])->name('guardian.children');
    Route::get('/dashboard', [GuardianController::class, 'dashboard'])->name('guardian.dashboard');
});
