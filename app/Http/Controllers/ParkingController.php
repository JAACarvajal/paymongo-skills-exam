<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ParkingService;

/**
 * Parking Controller
 */
class ParkingController extends Controller
{
    public $parkingService;
    public $request;

    /**
     * Constructor
     */
    public function __construct(Request $request, ParkingService $parkingService) {
        $this->request = $request;
        $this->parkingService = $parkingService;
    }

    /**
     * 
     */
    public function index() {
        return $this->parkingService->getParking();
    }

    /**
     * Initialize the parking
     */
    public function initializeParking() {
        return $this->parkingService->initializeParking($this->request);
    }

    /**
     * Park the vehicle
     */
    public function parkVehicle() {
        return $this->parkingService->parkVehicle($this->request);
    }

    /**
     * Unpark the vehicle
     */
    public function unparkVehicle() {
        return $this->parkingService->unparkVehicle($this->request);
    }
}
