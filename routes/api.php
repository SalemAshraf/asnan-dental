<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClinicDataController;
use App\Http\Middleware\ApiClinicContext;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', ApiClinicContext::class])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/appointments', [ClinicDataController::class, 'appointments']);
        Route::get('/patients', [ClinicDataController::class, 'patients']);
        Route::get('/reminders', [ClinicDataController::class, 'reminders']);
        Route::post('/reminders/{reminder}/sent', [ClinicDataController::class, 'markReminderSent']);
    });
});
