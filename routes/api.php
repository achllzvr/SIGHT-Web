<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SharedApiController;
use App\Http\Controllers\Api\GuardianApiController;
use App\Http\Controllers\Api\DoctorApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\AccessApiController;

// SHARED CLOUD API (Used by Web & Mobile)
Route::prefix('shared')->group(function () {
    Route::post('/login', [SharedApiController::class, 'login']);
    Route::post('/logout', [SharedApiController::class, 'logout'])->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/child/{child_id}/limits', [SharedApiController::class, 'getChildLimits']);
        Route::get('/child/{child_id}/metrics', [SharedApiController::class, 'getChildMetrics']);
    });
});

// WEB-ONLY CLOUD API
Route::prefix('web')->middleware(['auth:sanctum', 'web.api'])->group(function () {
    Route::prefix('guardian')->group(function () {
        Route::post('/register', [GuardianApiController::class, 'register'])->withoutMiddleware('auth:sanctum');
        Route::post('/child/add', [GuardianApiController::class, 'addChild']);
        Route::put('/child/{child_id}/limits', [GuardianApiController::class, 'updateChildLimits']);
        Route::delete('/child/{child_id}', [GuardianApiController::class, 'deleteChild']);
    });

    Route::prefix('doctor')->group(function () {
        Route::post('/access/redeem', [AccessApiController::class, 'redeem']);
        Route::get('/access-sessions/active', [AccessApiController::class, 'clinicianActiveSession']);
        Route::post('/access-sessions/{id}/end', [AccessApiController::class, 'endSessionClinician']);
        Route::get('/access-logs', [AccessApiController::class, 'clinicianAccessLogs']);
        Route::get('/access-sessions/{id}/telemetry', [AccessApiController::class, 'sessionTelemetry']);
        Route::get('/access-sessions/{id}/report.pdf', [AccessApiController::class, 'sessionReportPdf']);
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/analytics', [AdminApiController::class, 'getAnalytics']);
        Route::post('/create-professional', [AdminApiController::class, 'createProfessional']);
    });
});

// MOBILE-ONLY CLOUD API
Route::prefix('mobile')->group(function () {
    Route::post('/child/login', [MobileApiController::class, 'loginChild']);

    Route::post('/guardian/register', [GuardianApiController::class, 'registerMobile']);
    Route::post('/child/register', [GuardianApiController::class, 'addChildMobile']);
    Route::post('/guardian/verify-email', [GuardianApiController::class, 'verifyEmailMobile']);
    Route::post('/guardian/reset-password', [GuardianApiController::class, 'resetPasswordMobile']);

    Route::get('/legal-documents/latest', [AccessApiController::class, 'latestLegalDocuments']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/guardian/children', [GuardianApiController::class, 'getChildrenMobile']);

        Route::post('/children/{child_id}/access-tokens', [AccessApiController::class, 'generateToken']);
        Route::get('/children/{child_id}/access-session', [AccessApiController::class, 'childActiveSession']);
        Route::post('/access-sessions/{id}/end', [AccessApiController::class, 'endSessionMobile']);
        Route::get('/children/{child_id}/access-logs', [AccessApiController::class, 'childAccessLogs']);
        Route::post('/activity-logs', [AccessApiController::class, 'ingestActivity']);
        Route::post('/legal-agreements', [AccessApiController::class, 'acceptLegalDocuments']);

        Route::post('/child/{child_id}/sync/metrics', [MobileApiController::class, 'syncMetrics']);
        Route::post('/child/{child_id}/sync/metrics/batch', [MobileApiController::class, 'ingestBatchMetrics']);
        Route::put('/child/{child_id}/sync/pet', [MobileApiController::class, 'syncPet']);
        Route::put('/child/{child_id}/sync/limits', [MobileApiController::class, 'syncSessionLimits']);
        Route::post('/child/{child_id}/sync/calibration', [MobileApiController::class, 'syncCalibration']);
        Route::post('/device/register-token', [MobileApiController::class, 'registerFCMToken']);
        Route::post('/device/ping', [MobileApiController::class, 'devicePing']);
    });

    Route::get('/config', [MobileApiController::class, 'getConfig']);
});
