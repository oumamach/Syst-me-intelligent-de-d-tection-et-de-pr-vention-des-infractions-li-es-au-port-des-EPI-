<?php

use App\Http\Controllers\Api\AnomalieController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OperateurController;
use App\Http\Controllers\Api\CameraController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Publiques (Accès direct pour le Frontend & le Moteur IA)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

// Route pour l'analyse IA automatique depuis la caméra
Route::post('/detecter-anomalie', [AnomalieController::class, 'detecterAnomalie']);

// Route appelée par les scripts externes (Python / Hugging Face)
Route::post('/anomalies', [AnomalieController::class, 'store']);

// Routes de consultation ouvertes pour le dev/simulation
Route::get('/cameras', [CameraController::class, 'index']);
Route::get('/operateurs', [OperateurController::class, 'index']);
Route::post('/operateurs', [OperateurController::class, 'store']);
Route::delete('/operateurs/{operateur}', [OperateurController::class, 'destroy']);
Route::get('/anomalies', [AnomalieController::class, 'index']);
Route::get('/statistiques', [AnomalieController::class, 'statistiques']);

/*
|--------------------------------------------------------------------------
| Routes Protégées par Sanctum (Authentification requise)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/anomalies/{anomalie}', [AnomalieController::class, 'show']);
    Route::post('/cameras', [CameraController::class, 'store']);
    Route::delete('/cameras/{camera}', [CameraController::class, 'destroy']);
});