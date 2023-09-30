<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\ParkingController;

Route::prefix('parking')->controller(ParkingController::class)->group(function () {
    Route::get('/taken-slots', 'getTakenSlots');
    Route::post('/initialize', 'initializeParking');
    Route::post('/park', 'parkVehicle');
    Route::post('/unpark', 'unparkVehicle');
});