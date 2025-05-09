<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CepController;

Route::get('/healthy', fn() => ['message' => 'API OK']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('cep/{cep}', [CepController::class, 'inspect']);