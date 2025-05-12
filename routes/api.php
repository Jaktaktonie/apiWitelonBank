<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

Route::get('/test', [TestController::class, 'index']);
Route::post('/test', [TestController::class, 'store']);
Route::post('/send-mail', [TestController::class, 'sendMail']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
