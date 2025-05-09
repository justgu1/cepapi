<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CepController;

Route::get('/healthy', fn() => ['message' => 'API OK']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthenticatedSessionController::class, 'apiStore']);

Route::get('cep/{cep}', [CepController::class, 'inspect']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::post('/favorite/{cep}', [CepController::class, 'addToFavorites']);

    Route::get('/my-list', [CepController::class, 'myList']);
});
