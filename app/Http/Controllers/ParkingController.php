<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ParkingService;

class ParkingController extends Controller
{
    public $parkingService;
    public $request;

    /**
     * Constructor
     * 
     * @param Request $request
     * @param ParkingService $parkingService
     */
    public function __construct(Request $request, ParkingService $parkingService)
    {
        $this->request = $request;
        $this->parkingService = $parkingService;
    }

    /**
     * Get parking data
     * 
     * @return JsonResponse
     */
    public function getTakenSlots() : JsonResponse
    {
        return $this->parkingService->getTakenSlots();
    }

    /**
     * Get parking history
     * 
     * @return JsonResponse
     */
    public function getParkingHistory() : JsonResponse
    {
        return $this->parkingService->getParkingHistory();
    }

    /**
     * Initialize the parking 
     * 
     * @return JsonResponse
     */
    public function initializeParking() : JsonResponse
    {
        return $this->parkingService->initializeParking($this->request);
    }

    /**
     * Park the vehicle 
     * 
     * @return JsonResponse
     */
    public function parkVehicle() : JsonResponse
    {
        return $this->parkingService->parkVehicle($this->request);
    }

    /**
     * Unpark the vehicle
     * 
     * @return JsonResponse
     */
    public function unparkVehicle() : JsonResponse
    {
        return $this->parkingService->unparkVehicle($this->request);
    }

    /**
     * Unpark the vehicle
     * 
     * @return JsonResponse
     */
    public function clearParking() : JsonResponse
    {
        return $this->parkingService->clearParking();
    }
}
