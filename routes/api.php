<?php

use App\Http\Controllers\Api\AnomalieController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OperateurController;
use App\Http\Controllers\Api\CameraController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/anomalies', [AnomalieController::class, 'store']); // appelée par le script IA (Python/Hugging Face)

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/anomalies', [AnomalieController::class, 'index']);
    Route::get('/anomalies/{anomalie}', [AnomalieController::class, 'show']);
    Route::get('/statistiques', [AnomalieController::class, 'statistiques']);
    Route::get('/operateurs', [OperateurController::class, 'index']);
    Route::post('/operateurs', [OperateurController::class, 'store']);
    Route::delete('/operateurs/{operateur}', [OperateurController::class, 'destroy']);
    Route::get('/cameras', [CameraController::class, 'index']);
Route::post('/cameras', [CameraController::class, 'store']);
Route::delete('/cameras/{camera}', [CameraController::class, 'destroy']);
    
});
