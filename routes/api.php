<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreedController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

// Trasy publiczne
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Trasy chronione tokenem Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profil użytkownika
    Route::put('/profile/email', [ProfileController::class, 'updateEmail']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);
    Route::put('/profile/name', [ProfileController::class, 'updateName']); // Opcjonalnie zmiana imienia

    // Kalendarz i Wizyty
    Route::get('/visits', [VisitController::class, 'index']);
    Route::post('/visits', [VisitController::class, 'store']);
    Route::get('/visits/{visit}', [VisitController::class, 'show']);
    Route::put('/visits/{visit}', [VisitController::class, 'update']);
    Route::delete('/visits/{visit}', [VisitController::class, 'destroy']);

    // Klienci (pełny resource ze wsparciem dla GET /clients/{client})
    Route::apiResource('clients', ClientController::class);

    // Usługi
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);

    // Rasy
    Route::get('/breeds', [BreedController::class, 'index']);
    Route::post('/breeds', [BreedController::class, 'store']);
    Route::delete('/breeds/{breed}', [BreedController::class, 'destroy']);
});