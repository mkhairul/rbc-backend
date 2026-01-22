<?php

use App\Http\Controllers\ItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return ['status' => 'ok', 'message' => 'Pong!'];
});

// Items API Resource Routes
Route::apiResource('items', ItemController::class);

// Event history endpoint
Route::get('items/{id}/events', [ItemController::class, 'events']);
