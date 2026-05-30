<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GatewayController;

Route::prefix('v1')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/logout',  [AuthController::class, 'logout'])->middleware('jwt.auth');
    Route::get('/profile',  [AuthController::class, 'profile'])->middleware('jwt.auth');
});


Route::prefix('v1/gateway')->middleware(['jwt.auth'])->group(function () {


    Route::get('/containers',           [GatewayController::class, 'index']);
    Route::get('/containers/{id}',      [GatewayController::class, 'show']);
    Route::get('/containers/{id}/logs', [GatewayController::class, 'logs']);


    Route::post('/containers',                    [GatewayController::class, 'store'])->middleware('role:admin');
    Route::patch('/containers/{id}/archive',      [GatewayController::class, 'archive'])->middleware('role:admin');
    Route::delete('/containers/{id}',             [GatewayController::class, 'destroy'])->middleware('role:admin');
});