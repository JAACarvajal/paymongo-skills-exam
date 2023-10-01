<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParkingController;
use App\Http\Middleware\CheckParkingInitialized;

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

Route::middleware([CheckParkingInitialized::class])->prefix('parking')->controller(ParkingController::class)->group(function () {
    Route::post('/initialize', 'initializeParking')->withoutMiddleware([CheckParkingInitialized::class]);
    Route::get('/taken-slots', 'getTakenSlots');
    Route::get('/history', 'getParkingHistory');
    Route::post('/park', 'parkVehicle');
    Route::post('/unpark', 'unparkVehicle');
    Route::delete('/clear', 'clearParking');
});