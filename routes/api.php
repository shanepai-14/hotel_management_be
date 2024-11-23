<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;


    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/create-admin', [AuthController::class, 'createAdmin']); 
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('guests', GuestController::class);
        Route::apiResource('bookings', BookingController::class);

    });
