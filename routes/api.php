<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContainerController;

Route::get('/containers', [ContainerController::class, 'index']);
Route::get('/containers/search', [ContainerController::class, 'search']);
Route::get('/containers/{id}', [ContainerController::class, 'show']);
Route::get('/containers/{id}/logs', [ContainerController::class, 'logs']);

Route::post('/containers', [ContainerController::class, 'store']);
Route::patch('/containers/{id}/archive', [ContainerController::class, 'archive']);
Route::delete('/containers/{id}', [ContainerController::class, 'destroy']);