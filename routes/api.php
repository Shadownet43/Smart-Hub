<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipmentController;
use Illuminate\Support\Facades\Route;

/*
| Versi API v1 — prefix: /api/v1
*/

Route::prefix('v1')->group(function (): void {

    // Auth routes — public (tidak perlu token)
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Protected routes — semua butuh token
    Route::middleware('auth:sanctum')->group(function (): void {

        // Equipment Routes
        Route::prefix('equipment')->group(function (): void {

            // Public (semua user authenticated)
            Route::get('/', [EquipmentController::class, 'index']);
            Route::get('/{equipment}', [EquipmentController::class, 'show']);

            // Admin only
            Route::middleware('admin-only')->group(function (): void {
                Route::post('/', [EquipmentController::class, 'store']);
                Route::put('/{equipment}', [EquipmentController::class, 'update']);
                Route::patch('/{equipment}', [EquipmentController::class, 'update']);
                Route::delete('/{equipment}', [EquipmentController::class, 'destroy']);
            });
        });
    });
});
