<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SharedApiController;
use App\Http\Controllers\Api\GuardianApiController;
use App\Http\Controllers\Api\DoctorApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\MobileApiController;

// 🌐 SHARED CLOUD API (Used by Web & Mobile)
Route::prefix('shared')->group(function () {
    // Authentication
    Route::post('/login', [SharedApiController::class, 'login']);
    Route::post('/logout', [SharedApiController::class, 'logout'])->middleware('auth:sanctum');
    
    // Child Data (Shared between web and mobile)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/child/{child_id}/limits', [SharedApiController::class, 'getChildLimits']);
        Route::get('/child/{child_id}/metrics', [SharedApiController::class, 'getChildMetrics']);
    });
});

// 💻 WEB-ONLY CLOUD API (Laravel Frontend Only)
Route::prefix('web')->middleware(['auth:sanctum', 'web.api'])->group(function () {
    
    // Guardian Controls
    Route::prefix('guardian')->group(function () {
        Route::post('/register', [GuardianApiController::class, 'register'])->withoutMiddleware('auth:sanctum');
        Route::post('/child/add', [GuardianApiController::class, 'addChild']);
        Route::put('/child/{child_id}/limits', [GuardianApiController::class, 'updateChildLimits']);
        Route::post('/doctor/link', [GuardianApiController::class, 'linkDoctor']);
        Route::delete('/child/{child_id}', [GuardianApiController::class, 'deleteChild']);
    });
    
    // Clinician Portal
    Route::prefix('doctor')->group(function () {
        Route::get('/{doctor_id}/requests', [DoctorApiController::class, 'getPendingRequests']);
        Route::put('/requests/{link_id}', [DoctorApiController::class, 'respondToRequest']);
        Route::get('/{doctor_id}/patients', [DoctorApiController::class, 'getPatients']);
        Route::get('/patient/{child_id}/analytics', [DoctorApiController::class, 'getAnalytics']);
        Route::get('/patient/{child_id}/report', [DoctorApiController::class, 'generateReport']);
    });
    
    // Admin Portal
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/analytics', [AdminApiController::class, 'getAnalytics']);
        Route::post('/create-professional', [AdminApiController::class, 'createProfessional']);
    });
});
// 📱 MOBILE-ONLY CLOUD API (Flutter App Only)
Route::prefix('mobile')->group(function () {
    // Child Login via Code
    Route::post('/child/login', [MobileApiController::class, 'loginChild']);
    
    // --- Mobile Authentication Routes ---
    Route::post('/guardian/register', [GuardianApiController::class, 'registerMobile']);
    Route::post('/child/register', [GuardianApiController::class, 'addChildMobile']);
    Route::post('/guardian/verify-email', [GuardianApiController::class, 'verifyEmailMobile']);
    Route::post('/guardian/reset-password', [GuardianApiController::class, 'resetPasswordMobile']);

    // Guardian Dashboard (Mobile)
    Route::get('/guardian/children', [\App\Http\Controllers\Api\GuardianApiController::class, 'getChildrenMobile']);
    Route::get('/child/{child_id}/prescriptions', [MobileApiController::class, 'getPrescriptions']);

    // Doctor Search & Connection (Mobile)
    Route::get('/doctors', [\App\Http\Controllers\Api\GuardianApiController::class, 'getAvailableDoctors']);
    Route::get('/child/{child_id}/clinician-links', [\App\Http\Controllers\Api\GuardianApiController::class, 'getChildClinicianLinks']);
    Route::post('/child/{child_id}/clinician-request', [\App\Http\Controllers\Api\GuardianApiController::class, 'requestClinicianConnection']);
    Route::delete('/clinician-request/{link_id}', [\App\Http\Controllers\Api\GuardianApiController::class, 'cancelClinicianConnection']);
    
    // Sync endpoints (Authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/child/{child_id}/sync/metrics', [MobileApiController::class, 'syncMetrics']);
        Route::post('/child/{child_id}/sync/metrics/batch', [MobileApiController::class, 'ingestBatchMetrics']);
        Route::put('/child/{child_id}/sync/pet', [MobileApiController::class, 'syncPet']);
        Route::put('/child/{child_id}/sync/limits', [MobileApiController::class, 'syncSessionLimits']);
        Route::post('/child/{child_id}/sync/calibration', [MobileApiController::class, 'syncCalibration']);
        
        // Device Management
        Route::post('/device/register-token', [MobileApiController::class, 'registerFCMToken']);
        Route::post('/device/ping', [MobileApiController::class, 'devicePing']);
    });
    
    // Public endpoint
    Route::get('/config', [MobileApiController::class, 'getConfig']);
});
