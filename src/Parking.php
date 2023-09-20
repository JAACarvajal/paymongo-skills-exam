<?php

namespace App;

use Carbon\Carbon;

class Parking {
    const FLAT_RATE = 40;
    const ADDITIONAL_CHARGES_PER_HOUR = [
        'S' => 20,
        'M' => 60,
        'L' => 100,
    ];

    public $takenSlots = [];
    public $vehicle;
    public $entryTime;
    public $exitTime;
    public $entrance;

    /**
     * The map of the parking slot. You are welcome to introduce a design that suits your approach. 
     * One suggested method, however, is to accept a list of tuples corresponding to the distance of each slot from every entry point. 
     * For example, if your parking system has three (3) entry points. The list of parking spaces may be the following: [(1,4,5), (3,2,3), ...], 
     * where the integer entry per tuple corresponds to the distance unit from every parking entry point (A, B, C).
     * 
     * [A, B, C]
     * A -> distance from entrance 1
     * B -> distance from entrance 2
     * C -> distance from entrance 3
     */
    public $parkingMap = [
        [1, 2, 3], // this array is ONE SLOT
        [3, 4, 5], // ANOTHER SLOT here...
        [2, 5, 6],
        [4, 7, 3],
        [5, 4, 2],
        [2, 9, 5],
        [3, 4, 4],
        [5, 2, 8],
        [7, 1, 5]
    ];

    /**
     * Parking slot sizes/types
     */
    public $parkingSlotSizes = [
        'L',
        'M',
        'S',
        'M',
        'L',
        'M',
        'S',
        'M',
        'L'
    ];

    /**
     * Getter for $type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Getter for $type
     */
    public function getTakenSlots() {
        return $this->takenSlots;
    }

    /**
     * Park vehicle
     */
    public function park($vehicle, $entrance, $entryTime) { 
        $this->vehicle = $vehicle;       
        $this->entryTime = $entryTime;
        $this->entrance = $entrance;

        $this->findClosestSlot();
    }

    /**
     * Unpark vehicle
     * 
     * @param $parkingSlotIndex
     * @param $exitTime
     */
    public function unpark($parkingSlotIndex, $exitTime) {
        $parkingSlotType = $this->parkingSlotSizes[$parkingSlotIndex]; // Get the parking slot size/type
        $entryTime = $this->takenSlots[$parkingSlotIndex]['entry_time']; // Get the entry time from $takenSlots

        // Calculate the parking total time, round up always (2.3 => 3, 3.4 => 4)
        $totalTime = ceil($entryTime->floatDiffInHours($exitTime));

        // Calculate the parking fee
        $parkingFee = $this->calculateFee($totalTime, $parkingSlotType);
    }

    /**
     * Calculate fee of a vehicle
     * Take note that exceeding hours are charged depending on parking slot size regardless of vehicle size.
     */
    private function calculateFee($totalTime, $parkingSlotType) {
        // If the total time is less than or equal to 3, return the flat rate
        if ($totalTime <= 3) {
            return self::FLAT_RATE;
        }

        $totalParkingFee = self::FLAT_RATE;
        $additionalFeePerHour = self::ADDITIONAL_CHARGES_PER_HOUR[$parkingSlotType];
        $additionalTimeSpent = $totalTime - 3;

        $totalParkingFee += $additionalTimeSpent * $additionalFeePerHour;

        print 'Total time: ' . $totalTime . "\r\n";
        print 'Additional time: ' . $additionalTimeSpent . "\r\n";
        print 'Additional Rate per hour: ' . $additionalFeePerHour . "\r\n";
        print 'Total parking fee: ' . $totalParkingFee . "\r\n";

        return $totalParkingFee;
    }

    /**
     * Find the closest parking slot
     * A vehicle must be assigned a possible and available slot closest to the parking entrance
     */
    private function findClosestSlot() {
        $entranceIndex = $this->entrance - 1; // Extrance array index
        $closestSlotDistance = PHP_INT_MAX; // Initially set the parking slot distance to max value
        $closestSlotIndex = null; // Closest slot array index

        foreach ($this->parkingMap as $index => $parkingSlotArray) {
            $parkingSlotDistanceFromEntrance = $parkingSlotArray[$entranceIndex]; // Get the element based from $entrance

            /**
             * Check if the parking slot if closer than the previous AND
             * If the slot is not in yet taken AND
             * If the vehicle type is compatible with the parking slot
             */
            if (
                $parkingSlotDistanceFromEntrance < $closestSlotDistance &&
                array_key_exists($index, $this->takenSlots) === false &&
                $this->seeVehicleCompatibility($index) === true
            ) {
                $closestSlotDistance = $parkingSlotDistanceFromEntrance;
                $closestSlotIndex = $index;
            }            
        }

        // Add to taken slot array
        if ($closestSlotIndex !== null) {
            $this->takenSlots[$closestSlotIndex] = [
                'plate_number'      => $this->vehicle->getPlateNumber(),
                'entry_time'        => $this->entryTime,
                'parking_slot_type' => $this->parkingSlotSizes[$closestSlotIndex],
                'entrance'          => $this->entrance,
            ];

            $this->entryTimeTrackerList[$closestSlotIndex] = $this->entryTime;
            return;
        }        
    }

    /**
     * Returns true if vehicle is compatible
     * (a) S vehicles can park in SP, MP, and LP parking spaces;
     * (b) M vehicles can park in MP and LP parking spaces; and
     * (c) L vehicles can park only in LP parking spaces.
     */
    public function seeVehicleCompatibility($index) {
        $vehicleType = $this->vehicle->getType();
        $parkingSlotType = $this->parkingSlotSizes[$index];

        // If vehicle is small, return true
        if ($vehicleType === 'S') {
            return true;
        }

        if ($vehicleType === 'M' && ($parkingSlotType === 'M' || $parkingSlotType === 'L')) {
            return true;
        }

        if ($vehicleType === 'L' && $parkingSlotType === 'L') {
            return true;
        }

        return false;
    }
}