<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response as ResponseHTTP;
use Exception;
use Carbon\Carbon;

use App\Models\Vehicle;
use App\Models\ParkingSlot;
use App\Http\Constants\ParkingConstants;
use App\Libraries\ResponseLibrary;

/**
 * Parking Service
 */
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
     * 
     */
    public function getParking()
    {
        return ResponseLibrary::createJSONResponse([
            'message' => 'success',
            'data'    => cache('taken_slots')->map(function (Collection $slot, int $parkingSlotNo) {
                return [
                    'parking_slot' => $parkingSlotNo,
                    'vehicle'      => [
                        'plate_number' => $slot->get('vehicle')->getPlateNumber(),
                        'size'         => $slot->get('vehicle')->getSize(),
                    ],
                    'entry_time'   => $slot->get('entry_time')->toDateTimeString()
                ];
            })
            ->values()
            ->all()
        ]);
    }

    /**
     * Initialize parking map
     * @param $request
     */
    public function initializeParking($request) : JsonResponse
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
                    throw new Exception('Invalid parking size.');
                }
            });

            // Check if count of parking slot sizes matches the count of parking slots available
            if ($parkingMapSlots->count() !== $parkingSlotSizes->count()) {
                throw new Exception('The count of parking slot sizes does not match the count of parking slots available.');
            }

            // Check if parking slots are valid, if valid, add slot to parkingMap else throw an error
            $parkingMapSlots->each(function (array $parkingSlotDistance, int $index) use ($parkingSlotSizes, $parkingMap) {
                $parkingSlotEntryDistanceCount = count($parkingSlotDistance);
                if ($parkingSlotEntryDistanceCount !== $numberOfEntrance || $parkingSlotEntryDistanceCount < 3) {
                    throw new Exception('Invalid parking slot.');
                }

                $parkingMap->push(new ParkingSlot($parkingSlotDistance, $parkingSlotSizes[$index]));
            });

            cache()->flush();
            cache()->put('parking_map', $parkingMap);
            cache()->put('parking_history', collect([]));
            cache()->put('taken_slots', collect([]));
            cache()->put('parking_map_slots', $parkingMapSlots);
            cache()->put('parking_slot_sizes', $parkingSlotSizes);
            cache()->put('number_of_entrance', $numberOfEntrance);
            cache()->put('parking_slot_size_list', $parkingSlotSizeList);
            cache()->put('additional_charge_per_hour', $additionalChargePerHour);

            return ResponseLibrary::createJSONResponse(['message' => 'success']);
        } catch (Exception $error) {
            return ResponseLibrary::createJSONResponse(['message' => $error->getMessage()], ResponseHTTP::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Park vehicle
     * @param $request
     */
    public function parkVehicle($request) : JsonResponse
    {        
        // Check if vehicle already parked
        if ($this->checkExistingVehicle($request->plate_number) === true) {
            return ResponseLibrary::createJSONResponse(['message' => 'Vehicle already parked.'], ResponseHTTP::HTTP_BAD_REQUEST);
        }

        // Check if vehicle size is valid
        if (cache('parking_slot_size_list')->contains($request->vehicle_size) === false) {
            return ResponseLibrary::createJSONResponse(['message' => 'Invalid vehicle size.'], ResponseHTTP::HTTP_BAD_REQUEST);
        }

        /* Get data from cache */
        $parkingMap = cache('parking_map');
        $takenSlots = cache('taken_slots');

        $this->vehicle = new Vehicle($request->vehicle_size, $request->plate_number); // Create new vehicle
        $this->entryTime = Carbon::parse($request->entry_time); // Set entry time property
        $entryPoint = (int) $request->entry_point;

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
                $takenSlots->has($index) === false &&
                $this->checkVehicleCompatibility($parkingMap->get($index)) === true
            ) {
                $closestSlotDistance = $parkingSlotDistanceFromEntrance;
                $closestSlotIndex = $index;
            }           
        }

        // If there is an available slot
        if ($closestSlotIndex !== null) {
            return $this->assignSlot($closestSlotIndex);
        }

        //  Return if there is no availble parking slot
        return ResponseLibrary::createJSONResponse([
            'message' => 'No parking slot found for the vehicle',
            'vehicle' => [
                'plate_number' => $this->vehicle->getPlateNumber(),
                'size'         => $this->vehicle->getSize()
            ]
        ], ResponseHTTP::HTTP_NOT_FOUND);
    }

    /**
     * @param $request
     */
    public function unparkVehicle($request) {
        $takenSlots = cache('taken_slots');
        $parkingHistory = cache('parking_history');
        $parkingSlotIndex = (int) $request->slot_no - 1; // Get parking slot index
        $exitTime = Carbon::parse($request->exit_time);

        // Check if a vehice is parked on the parking slot
        if ($takenSlots->has($parkingSlotIndex) === false) {
            return ResponseLibrary::createJSONResponse(['message' => 'No parked vehicle.'], ResponseHTTP::HTTP_BAD_REQUEST);
        }

        $parkingSlotData = $takenSlots->get($parkingSlotIndex); // Get parking slot data
        $this->vehicle = $parkingSlotData->get('vehicle'); // Set the vehicle

        // Calculate the parking total time, round up always (2.3 => 3, 3.4 => 4)
        $totalTime = ceil(round($parkingSlotData->get('entry_time')->floatDiffInHours($exitTime), 2));

        // Calculate the parking fee
        $totalParkingFee = $this->calculateFee($totalTime, $parkingSlotIndex);

        // Record parking
        cache()->put('parking_history', $parkingHistory->put($this->vehicle->getPlateNumber(), collect([
            'entry_time' => $takenSlots->get($parkingSlotIndex)->get('entry_time'),
            'exit_time'  => $exitTime
        ])));

        // Remove vehicle from slot
        cache()->put('taken_slots', $takenSlots->forget($parkingSlotIndex));

        return ResponseLibrary::createJSONResponse([
            'message'     => 'success',
            'parking_fee' => $totalParkingFee,
            'vehicle'     => [
                'plate_number' => $this->vehicle->getPlateNumber(),
                'size'         => $this->vehicle->getSize()
            ],
            'exit_time'   => $exitTime->toDateTimeString()
        ], ResponseHTTP::HTTP_OK);
    }

    /**
     * Calculate fee of the parking slot
     * @param $totalTime
     * @param $parkingSlotType
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
     * @param $closestSlotIndex
     */
    private function assignSlot(int $closestSlotIndex) : JsonResponse 
    {
        $entryTime = $this->entryTime;
        $parkingHistory = cache('parking_history');
        $takenSlots = cache('taken_slots');
        
        /**
         * If vehicle has parking history AND
         * if vehicle left the parking complex and returned within one hour 
         */
        if (
            $parkingHistory->has($plateNumber = $this->vehicle->getPlateNumber()) === true &&
            $this->entryTime->floatDiffInHours($parkingHistory->get($plateNumber)['exit_time']) <= 1
        ) {
            $entryTime = $parkingHistory->get($this->vehicle->getPlateNumber())['entry_time'];
        }

        // Push data to taken slots
        cache()->put('taken_slots', $takenSlots->put($closestSlotIndex, collect([
            'vehicle'    => $this->vehicle,
            'entry_time' => $entryTime
        ])));

        return ResponseLibrary::createJSONResponse([
            'message' => 'success',
            'vehicle' => [
                'plate_number' => $this->vehicle->getPlateNumber(),
                'size'         => $this->vehicle->getSize(),
                'entry_time'   => $entryTime->toDateTimeString()
            ],
            'slot'    => $closestSlotIndex + 1

        ]);
    }

    /**
     * Returns true if vehicle is compatible with the parking slot
     * @param $parkingSlot
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
     * @param $plateNumber
     */
    private function checkExistingVehicle(string $plateNumber) : bool
    {
        $exists = false;

        cache('taken_slots')->each(function ($parkingSlotdata, int $parkingSlotNumber) use ($plateNumber, &$exists) {
            if ($parkingSlotdata->get('vehicle')->getPlateNumber() === $plateNumber) {
                $exists = true;
                return false;
            }
        });

        return $exists;
    }
}
