<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/healthy', fn () => ['message' => 'API OK']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
