<?php

use App\Http\Controllers\Api\Admin\AdminKontoController;
use App\Http\Controllers\Api\Admin\AdminPrzelewController;
use App\Http\Controllers\Api\Admin\AdminRaportController;
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

Route::post('/forgot-password', [UzytkownikController::class, 'forgotPassword'])->name('password.request');
Route::post('/reset-password', [UzytkownikController::class, 'resetPassword'])->name('password.reset'); // lub password.update

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

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Zarządzanie Kontami Użytkowników
    Route::get('konta', [AdminKontoController::class, 'index'])->name('konta.index'); // WBK-02
    Route::get('konta/{konto}', [AdminKontoController::class, 'show'])->name('konta.show'); // WBK-02
    Route::patch('konta/{konto}/block', [AdminKontoController::class, 'blockAccount'])->name('konta.block'); // WBK-02
    Route::patch('konta/{konto}/unblock', [AdminKontoController::class, 'unblockAccount'])->name('konta.unblock'); // WBK-02
    Route::patch('konta/{konto}/limit', [AdminKontoController::class, 'updateLimit'])->name('konta.limit'); // WBK-02

    // Monitorowanie Transakcji (Przelewów)
    Route::get('przelewy', [AdminPrzelewController::class, 'index'])->name('przelewy.index'); // WBK-03

    // Generowanie Raportów Finansowych
    Route::get('raporty/przelewy', [AdminRaportController::class, 'financialTransfersReport'])->name('raporty.przelewy'); // WBK-04
});

use App\Http\Controllers\Api\InwestycjaController;


Route::middleware('auth:sanctum')->group(function () {
    // ... inne trasy chronione ...
    Route::get('/konta/{konto}/przelewy', [PrzelewController::class, 'indexForAccount']); // Pamiętaj, że masz to

    // Inwestycje
    Route::get('/kryptowaluty/ceny', [InwestycjaController::class, 'pobierzCeny'])->name('krypto.ceny');
    Route::post('/inwestycje/kup', [InwestycjaController::class, 'kup'])->name('inwestycje.kup');

    Route::get('/portfel', [InwestycjaController::class, 'pobierzMojPortfel'])->name('portfel.show');
});
