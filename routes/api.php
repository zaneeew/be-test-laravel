<?php

use Illuminate\Support\Facades\Route;

Route::post('/todos', [\App\Http\Controllers\Api\TodoController::class, 'store']);
Route::get('/todos/export', [\App\Http\Controllers\Api\TodoController::class, 'export']);
Route::get('/chart', [\App\Http\Controllers\Api\TodoController::class, 'chart']);