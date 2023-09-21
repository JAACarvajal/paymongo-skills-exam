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
    public $parkingHistory = [];
    public $vehicle;
    public $entryTime;
    public $exitTime;
    public $entrance;

    /**
     * Parking map
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
        [7, 1, 5],
        [4, 3, 1],
    ];

    /**
     * Parking slot sizes/types of slots in parkingMap 
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
        'L',
        'M'
    ];

    /**
     * Getter for $takenSlots
     */
    public function getTakenSlots() {
        return $this->takenSlots;
    }

    /**
     * Getter for $parkingHistory
     */
    public function getParkingHistory() {
        return $this->parkingHistory;
    }

    /**
     * Getter for $parkingHistory
     */
    public function print() {
        //  TODO -> PRINT FUNCTION
    }

    /**
     * Park vehicle
     * @param $vehicle
     * @param $entrance
     * @param $entryTime
     */
    public function park($vehicle, $entrance, $entryTime) { 
        $this->vehicle = $vehicle;       
        $this->entryTime = $entryTime;
        $this->entrance = $entrance;
        $this->findClosestSlot();
    }

    /**
     * Unpark vehicle
     * @param $parkingSlot
     * @param $exitTime
     */
    public function unpark($parkingSlot, $exitTime) {
        // Get parking slot index
        $parkingSlotIndex = $parkingSlot - 1;

        // Check if a vehice is parked on the parking slot
        if (array_key_exists($parkingSlotIndex, $this->takenSlots) === false) {
            print 'No vehicle parked at slot ' . $parkingSlot . "\r\n";
            return;
        }

        // Calculate the parking total time, round up always (2.3 => 3, 3.4 => 4)
        $totalTime = ceil(round($this->takenSlots[$parkingSlotIndex]['entry_time']->floatDiffInHours($exitTime), 2));
        
        // Calculate the parking fee
        $this->calculateFee($totalTime, $parkingSlotIndex);

        // Record parking
        $this->parkingHistory[$this->takenSlots[$parkingSlotIndex]['plate_number']] = [
            'entry_time' => $this->takenSlots[$parkingSlotIndex]['entry_time'],
            'exit_time'  => $exitTime
        ];

        // Remove the slot from $takenSlots
        unset($this->takenSlots[$parkingSlotIndex]);
    }

    /**
     * Calculate fee of a vehicle
     * @param $totalTime
     * @param $parkingSlotType
     */
    private function calculateFee($totalTime, $parkingSlotIndex) {
        $parkingSlotType = $this->parkingSlotSizes[$parkingSlotIndex]; // Get the parking slot size/type
        $totalParkingFee = self::FLAT_RATE; // Set initial fee to flat rate
        $additionalFeePerHour = self::ADDITIONAL_CHARGES_PER_HOUR[$parkingSlotType];
        $additionalTimeSpent = 0;

        if ($totalTime > 3 && $totalTime <= 24) {
            $additionalTimeSpent = $totalTime - 3;
            $totalParkingFee += $additionalTimeSpent * $additionalFeePerHour;
        }

        if ($totalTime > 24) {
            $totalParkingFee = 0;
            $fullDayCount = floor($totalTime / 24);
            $totalParkingFee += 5000 * $fullDayCount;
            $additionalTimeSpent = $totalTime - ($fullDayCount * 24);
            $totalParkingFee += $additionalTimeSpent * $additionalFeePerHour;
        }
        
        $vehicleParked = $this->takenSlots[$parkingSlotIndex]['plate_number'];

        print "-------------------------------- \r\n";
        print 'Vehicle: ' . $vehicleParked . "\r\n";
        print 'Parking Slot: ' . $parkingSlotIndex + 1 . "\r\n";
        print 'Total time: ' . $totalTime . "\r\n";
        print 'Additional time (rounded up in hours): ' . $additionalTimeSpent . "\r\n";
        print 'Additional Rate per hour: ' . $additionalFeePerHour . "\r\n";
        print 'Total parking fee: ' . $totalParkingFee . "\r\n";
        print "-------------------------------- \r\n";

        return $totalParkingFee;
    }

    /**
     * Find the closest parking slot
     * A vehicle must be assigned a possible and available slot closest to the parking entrance
     */
    private function findClosestSlot() {
        // @TODO check parking history
        $entranceIndex = $this->entrance - 1; // Entrance array index
        $closestSlotDistance = PHP_INT_MAX; // Initially set the parking slot distance to max value
        $closestSlotIndex = null; // Closest slot array index

        foreach ($this->parkingMap as $index => $parkingSlotArray) {
            // Get the distance from the entrance
            $parkingSlotDistanceFromEntrance = $parkingSlotArray[$entranceIndex]; 

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
            $this->assignSlot($closestSlotIndex);
            return; 
        }
        
        // If there are no available slots
        print "Vehicle " . $this->vehicle->getPlateNumber() . " (" . $this->vehicle->getType() .  ") entered from Entrance $this->entrance and was not able to park. \r\n";
    }

    /**
     * Assign a parking slot
     */
    private function assignSlot($closestSlotIndex, $hasParkHistory = false) {
        $entryTime = $this->entryTime;

        /**
         * Check if vehicle has parking history AND
         * if vehicle the leaving the parking complex and returning within one hour based on their exit time must be charged a continuous rate
         */
        if (
            array_key_exists($this->vehicle->getPlateNumber(), $this->parkingHistory) === true &&
            $this->entryTime->floatDiffInHours($this->parkingHistory[$this->vehicle->getPlateNumber()]['exit_time']) <= 1
        ) {
            $entryTime = $this->parkingHistory[$this->vehicle->getPlateNumber()]['entry_time'];
        }

        $this->takenSlots[$closestSlotIndex] = [
            'plate_number'      => $this->vehicle->getPlateNumber(),
            'entry_time'        => $entryTime,
            'parking_slot_type' => $this->parkingSlotSizes[$closestSlotIndex],
            'entrance'          => $this->entrance,
        ];

        print "Vehicle " . $this->vehicle->getPlateNumber() . " (" . $this->vehicle->getType() . ") entered from Entrance $this->entrance at " . $entryTime->format('Y-m-d h:i:s') . " and parked at slot " . $closestSlotIndex + 1 . " (" . $this->parkingSlotSizes[$closestSlotIndex] . ") \r\n";
    }

    /**
     * Returns true if vehicle is compatible
     * @param $index
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