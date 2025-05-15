<?php

use App\Http\Controllers\Api\PrzelewController;
use App\Http\Controllers\UzytkownikController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Api\KontoController; // Popraw ścieżkę jeśli trzeba

Route::get('/test', [TestController::class, 'index']);
Route::post('/test', [TestController::class, 'store']);
Route::post('/send-mail', [TestController::class, 'sendMail']);
Route::get('/uzytkownicy', [UzytkownikController::class, 'index']);


Route::post('/login', [UzytkownikController::class, 'login']);
Route::post('/2fa', [UzytkownikController::class, 'verifyTwoFactor']);

// Trasa do wylogowania (wymaga autentykacji)
Route::middleware('auth:sanctum')->post('/logout', [UzytkownikController::class, 'logout']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    // ... inne trasy chronione ...
    Route::get('/konta', [KontoController::class, 'index']); // Pobiera listę kont użytkownika
    Route::get('/konta/{konto}', [KontoController::class, 'show']); // Pobiera szczegóły konkretnego konta (z Route Model Binding)
});

Route::middleware('auth:sanctum')->group(function () {
    // ... inne trasy ...

    Route::post('/przelewy', [PrzelewController::class, 'store']);
    Route::get('/konta/{idKonta}/przelewy', [PrzelewController::class, 'index']);
    Route::get('/przelewy/{idPrzelewu}', [PrzelewController::class, 'show']);
});
