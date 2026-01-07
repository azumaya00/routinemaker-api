<?php

use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\HistoriesController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\RoutineController;
use App\Http\Controllers\Api\UserSettingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    // SPA ログアウトは API 側で完結させ、redirect を避ける。
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::get('/routines', [RoutineController::class, 'index']);
    Route::post('/routines', [RoutineController::class, 'store']);
    Route::get('/routines/{id}', [RoutineController::class, 'show']);
    Route::patch('/routines/{id}', [RoutineController::class, 'update']);
    Route::delete('/routines/{id}', [RoutineController::class, 'destroy']);

    Route::post('/routines/{id}/start', [HistoryController::class, 'start']);
    Route::get('/histories', [HistoriesController::class, 'index']);
    Route::get('/histories/{history}', [HistoriesController::class, 'show']);
    Route::post('/histories/{id}/complete', [HistoryController::class, 'complete']);
    Route::post('/histories/{id}/abort', [HistoryController::class, 'abort']);

    Route::get('/settings', [UserSettingController::class, 'show']);
    Route::patch('/settings', [UserSettingController::class, 'update']);
});
