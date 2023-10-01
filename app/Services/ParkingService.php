<?php

namespace App\Services;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response as ResponseHTTP;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;

use App\Models\{Vehicle, ParkingSlot};
use App\Http\Constants\ParkingConstants;
use App\Libraries\ResponseLibrary;

class ParkingService extends ParkingConstants
{
    /**
     * Vehicle
     */
    public $vehicle;

    /**
     * Vehicle entry time
     */
    public $entryTime;

    /**
     * Get parking data
     * 
     * @return JsonResponse
     */
    public function getTakenSlots(): JsonResponse
    {
        $parkingMap = cache('parking_map');
        
        try {
            return ResponseLibrary::createJSONResponse(cache('taken_slots')->map(function (array $slot, int $parkingSlotNo) use ($parkingMap) {
                return [
                    'parking_slot' => [
                        'slot_no' => $parkingSlotNo,
                        'size'    => $parkingMap->get($parkingSlotNo - 1)->getSize()
                    ],
                    'vehicle'      => [
                        'plate_number' => $slot['plate_number'],
                        'size'         => $slot['vehicle_size'],
                    ],
                    'entry_time'   => $slot['entry_time']->toDateTimeString()
                ];
            })
            ->values()
            ->all());
        } catch (Exception $error) {
            return ResponseLibrary::createJSONResponse(null, $error->getMessage(), ResponseHTTP::HTTP_INTERNAL_SERVER_ERROR); 
        }
    }

    /**
     * Get parking history
     * 
     * @return JsonResponse
     */
    public function getParkingHistory(): JsonResponse
    {        
        try {
            return ResponseLibrary::createJSONResponse(cache('parking_history')->all());
        } catch (Exception $error) {
            return ResponseLibrary::createJSONResponse(null, $error->getMessage(), ResponseHTTP::HTTP_INTERNAL_SERVER_ERROR); 
        }
    }

    /**
     * Clear parking
     * 
     * @param $request
     * 
     * @return JsonResponse
     */
    public function clearParking() : JsonResponse
    {
        try {
            cache()->put('taken_slots', collect([]));
            cache()->put('parking_history', collect([]));
            return ResponseLibrary::createJSONResponse(null, 'success', ResponseHTTP::HTTP_OK);
        } catch (Exception $error) {
            return ResponseLibrary::createJSONResponse(null, $error->getMessage(), ResponseHTTP::HTTP_INTERNAL_SERVER_ERROR); 
        }
    }

    /**
     * Initialize parking
     * 
     * @param $request
     * 
     * @return JsonResponse
     */
    public function initializeParking(Request $request) : JsonResponse
    {
        // Get data from request object
        $parkingMapSlots = collect(json_decode($request->parking_map, true));
        $parkingSlotSizes = collect(json_decode($request->parking_slot_sizes, true));
        $numberOfEntrance = (int) $request->number_of_entrance;
        $additionalChargePerHour = collect(json_decode($request->parking_flat_charges, true));
        $parkingSlotSizeList = collect($additionalChargePerHour->keys()->all());
        $parkingMap = collect([]);

        try {
            // Check if parking slot sizes are valid
            $parkingSlotSizes->each(function (string $parkingSlotSize, int $index) use ($parkingSlotSizeList) {
                if ($parkingSlotSizeList->contains($parkingSlotSize) === false) {
                    throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'Invalid parking size.');
                }
            });

            // Check if count of parking slot sizes matches the count of parking slots available
            if ($parkingMapSlots->count() !== $parkingSlotSizes->count()) {
                throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'The count of parking slot sizes does not match the count of parking slots available.');
            }

            // Check if parking slots are valid, if valid, add slot to parkingMap else throw an error
            $parkingMapSlots->each(function (array $parkingSlotDistance, int $index) use ($parkingSlotSizes, $parkingMap, $numberOfEntrance) {
                $parkingSlotEntryDistanceCount = count($parkingSlotDistance);
                if ($parkingSlotEntryDistanceCount !== $numberOfEntrance || $parkingSlotEntryDistanceCount < self::MIN_ENTRY_POINT) {
                    throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'Invalid parking slot.');
                }

                $parkingMap->push(new ParkingSlot($parkingSlotDistance, $parkingSlotSizes[$index]));
            });

            cache()->flush();
            cache()->put('is_created', true);
            cache()->put('parking_map', $parkingMap);
            cache()->put('parking_history', collect([]));
            cache()->put('taken_slots', collect([]));
            cache()->put('parking_map_slots', $parkingMapSlots);
            cache()->put('parking_slot_sizes', $parkingSlotSizes);
            cache()->put('number_of_entrance', $numberOfEntrance);
            cache()->put('parking_slot_size_list', $parkingSlotSizeList);
            cache()->put('additional_charge_per_hour', $additionalChargePerHour);

            return ResponseLibrary::createJSONResponse();
        } catch (HttpException $error) {
            return ResponseLibrary::createJSONResponse(null, $error->getMessage(), $error->getStatusCode());
        }
    }

    /**
     * Park vehicle
     * 
     * @param $request
     * 
     * @return JsonResponse
     */
    public function parkVehicle(Request $request) : JsonResponse
    {
        try {
            // Check if vehicle already parked
            if ($this->checkExistingVehicle($request->plate_number) === true) {
                throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'Vehicle already parked.');
            }

            // Check if vehicle size is valid
            if (cache('parking_slot_size_list')->contains($request->vehicle_size) === false) {
                throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'Invalid vehicle size.');
            }

            /* Get data from cache */
            $parkingMap = cache('parking_map');
            $takenSlots = cache('taken_slots');

            $this->vehicle = new Vehicle($request->vehicle_size, $request->plate_number); // Create new vehicle
            $this->entryTime = Carbon::parse($request->entry_time); // Set entry time property
            $entryPoint = (int) $request->entry_point; // Get entry point

            $closestSlotDistance = PHP_INT_MAX; // Initially set the parking slot distance to max value
            $closestSlotIndex = null; // Closest slot array index

            foreach ($parkingMap->all() as $index => $parkingSlot) {
                // Get the distance from the entrance
                $parkingSlotDistanceFromEntrance = $parkingSlot->getDistance($entryPoint - 1);

                /**
                 * Check if the parking slot if closer than the previous AND
                 * If the slot is not in yet taken AND
                 * If the vehicle type is compatible with the parking slot
                 */
                if (
                    $parkingSlotDistanceFromEntrance < $closestSlotDistance &&
                    $takenSlots->has($index + 1) === false &&
                    $this->checkVehicleCompatibility($parkingMap->get($index)) === true
                ) {
                    $closestSlotDistance = $parkingSlotDistanceFromEntrance;
                    $closestSlotIndex = $index;
                }           
            }

            // If there is an available slot
            if ($closestSlotIndex !== null) {
                return ResponseLibrary::createJSONResponse($this->assignSlot($closestSlotIndex), 'success');
            }

            //  Return 404 if there is no availble parking slot
            return ResponseLibrary::createJSONResponse([
                'vehicle'      => [
                    'plate_number' => $this->vehicle->getPlateNumber(),
                    'size'         => $this->vehicle->getSize()
                ]
            ], 'No parking slot found for the vehicle', ResponseHTTP::HTTP_NOT_FOUND);
        } catch (HttpException $error) {
            return ResponseLibrary::createJSONResponse(null, $error->getMessage(), $error->getStatusCode());
        }
    }

    /**
     * Unpack Vehicle
     * 
     * @param $request
     * 
     * @return JsonResponse
     */
    public function unparkVehicle(Request $request) : JsonResponse
    {
        $plateNumber = $request->plate_number; // Get plate number
        $exitTime = Carbon::parse($request->exit_time);

        try {
            // Get data from cache
            $parkingMap = cache('parking_map');
            $takenSlots = cache('taken_slots');
            $parkingHistory = cache('parking_history');
            $parkingSlotNo = $takenSlots->where('plate_number', $plateNumber)->keys()->first(); // Get parking slot number
            
            // Check if a vehice is parked on the parking slot
            if ($takenSlots->has($parkingSlotNo) === false) {
                throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'No parked vehicle.');
            }

            $parkingSlotData = $takenSlots->get($parkingSlotNo); // Get parking slot data
            $startTime = $parkingSlotData['entry_time']; // Get start time
            $this->vehicle = $parkingSlotData['vehicle']; // Set the vehicle

            // Check if start time exceeds exit time
            if ($startTime->floatDiffInSeconds($exitTime, false) < 0) {
                throw new HttpException(ResponseHTTP::HTTP_BAD_REQUEST, 'Exit time cannot be lower than entry time.');
            }

            // Calculate the parking total time, round up always (2.3 => 3, 3.4 => 4)
            $totalTime = ceil(round($startTime->floatDiffInHours($exitTime), 2));

            // Calculate the parking fee
            $totalParkingFee = $this->calculateFee($totalTime, $parkingSlotNo - 1);

            // Create response data
            $responseData = [
                'parking_fee' => [
                    'amount'     => $totalParkingFee,
                    'total_hours' => $totalTime
                ],
                'vehicle'     => [
                    'plate_number' => $this->vehicle->getPlateNumber(),
                    'size'         => $this->vehicle->getSize()
                ],
                'parking_slot' => [
                    'slot_no' => $parkingSlotNo,
                    'size'    => $parkingMap->get($parkingSlotNo - 1)->getSize()
                ],
                'entry_time'   => $startTime->toDateTimeString(),
                'exit_time'    => $exitTime->toDateTimeString()
            ];

            // Record parking
            cache()->put('parking_history', $parkingHistory->push($responseData));
            // Remove vehicle from slot
            cache()->put('taken_slots', $takenSlots->forget($parkingSlotNo));

            return ResponseLibrary::createJSONResponse($responseData, 'success');
        } catch (HttpException $error) {
            return ResponseLibrary::createJSONResponse(null, $error->getMessage(), $error->getStatusCode());
        }    
    }

    /**
     * Calculate fee of the parking slot
     * 
     * @param $totalTime
     * @param $parkingSlotType
     * 
     * @return int
     */
    private function calculateFee(int $totalTime, int $parkingSlotIndex) : int
    {
        $parkingMap = cache('parking_map');
        $totalParkingFee = self::FLAT_RATE; // Set initial fee to flat rate
        $additionalFeePerHour = cache('additional_charge_per_hour')->get($parkingMap->get($parkingSlotIndex)->getSize()); // Get the additional change
        $additionalTimeSpent = 0; // Set initial additional time spent to 0

        // If the total time is greater than 3 hours and less than 24 hours
        if ($totalTime > self::FLAT_RATE_TOTAL_HOURS && $totalTime <= self::HOURS_PER_DAY) {
            $additionalTimeSpent = $totalTime - self::FLAT_RATE_TOTAL_HOURS;
            $totalParkingFee += $additionalTimeSpent * $additionalFeePerHour;
        }

        // If the total time exceeds 24 hours
        if ($totalTime > self::HOURS_PER_DAY) {
            $totalParkingFee = 0; // Set the total parking fee to 0
            $fullDayCount = floor($totalTime / self::HOURS_PER_DAY); // Get the count of every 24-hour/1-day chunk (eg. if $totalTime is 49 hours, the full day count would be 2)
            $totalParkingFee += self::FULL_DAY_CHARGE_RATE * $fullDayCount; // Add calculated fee to the total parking fee

            // Get the additional time (eg. if $totalTime is 49 hours, the full day count would be 2 and the additional time would be 1 hour)
            $additionalTimeSpent = $totalTime - ($fullDayCount * self::HOURS_PER_DAY);
            $totalParkingFee += $additionalTimeSpent * $additionalFeePerHour;
        }

        return $totalParkingFee;
    }

    /**
     * Assign a parking slot
     * 
     * @param $closestSlotIndex
     * 
     * @return JsonResponse
     */
    private function assignSlot(int $closestSlotIndex) : array 
    {
        // Get data from cache
        $parkingMap = cache('parking_map');
        $parkingHistory = cache('parking_history');
        $takenSlots = cache('taken_slots');

        $entryTime = $this->entryTime;
        $plateNumber = $this->vehicle->getPlateNumber();
        $vehicleLatestParkingHistory = $parkingHistory->where('vehicle.plate_number', $plateNumber)->last(); // Get latest parking history of the vehicle 

        /**
         * If vehicle has parking history AND
         * if vehicle left the parking complex and returned within one hour 
         */
        if (
            $vehicleLatestParkingHistory !== null &&
            $this->entryTime->floatDiffInHours($vehicleLatestParkingHistory['exit_time']) <= 1
        ) {
            $entryTime = Carbon::parse($vehicleLatestParkingHistory['entry_time']);
        }

        // Push data to taken slots
        cache()->put('taken_slots', $takenSlots->put($closestSlotIndex + 1, [
            'vehicle'      => $this->vehicle,
            'plate_number' => $plateNumber,
            'vehicle_size' => $this->vehicle->getSize(),
            'entry_time'   => $entryTime
        ]));

        return [
            'vehicle'      => [
                'plate_number' => $this->vehicle->getPlateNumber(),
                'size'         => $this->vehicle->getSize(),
            ],
            'parking_slot' => [
                'slot_no' => $closestSlotIndex + 1,
                'size'    => $parkingMap->get($closestSlotIndex)->getSize()
            ],
            'entry_time'   => $entryTime->toDateTimeString()
        ];
    }

    /**
     * Returns true if vehicle is compatible with the parking slot
     * 
     * @param $parkingSlot
     * 
     * @return bool
     */
    private function checkVehicleCompatibility(ParkingSlot $parkingSlot) : bool
    {
        $vehicleType = $this->vehicle->getSize();
        $parkingSlotSize = $parkingSlot->getSize();

        return (
            $vehicleType === 'S' ||
            ($vehicleType === 'M' && ($parkingSlotSize === 'M' || $parkingSlotSize === 'L')) ||
            ($vehicleType === 'L' && $parkingSlotSize === 'L')
        );
    }

    /**
     * Check if vehicle is already parked
     * 
     * @param $plateNumber
     * 
     * @return bool
     */
    private function checkExistingVehicle(string $plateNumber) : bool
    {
        $isParked = false;

        // Loop through taken slots and check if vehicle already exist
        cache('taken_slots')->each(function ($parkingSlotdata, int $parkingSlotNumber) use ($plateNumber, &$isParked) {
            if ($parkingSlotdata['vehicle']->getPlateNumber() === $plateNumber) {
                $isParked = true;
                return false;
            }
        });

        return $isParked;
    }
}
