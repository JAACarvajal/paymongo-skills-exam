<?php

namespace App;

use Carbon\Carbon;

class Parking {
    const FLAT_RATE = 40;

    public $takenSlots = [];
    public $entryTimeTrackerList = [];
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
     * Getter for $type
     */
    public function getEntryTimeTrackerList() {
        return $this->entryTimeTrackerList;
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
     * Park vehicle
     */
    public function unpark($parkingSlotIndex, $exitTime) {
        $parkingSlotType = $this->parkingSlotSizes[$parkingSlotIndex]; // Get the parking slot size/type
        $entryTime = $this->entryTimeTrackerList[$parkingSlotIndex]; // Get the entry time from $entryTimeTrackerList

        // $totalTime = $exitTime - $entryTime;
        echo self::FLAT_RATE;
    }

    /**
     * Calculate fee of a vehicle
     */
    private function calculateFee() {}

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
                in_array($index, $this->takenSlots) === false &&
                $this->seeVehicleCompatibility($index) === true
            ) {
                $closestSlotDistance = $parkingSlotDistanceFromEntrance;
                $closestSlotIndex = $index;
            }            
        }

        // Add to taken slot array
        if ($closestSlotIndex !== null) {
            $this->takenSlots[] = $closestSlotIndex;
            $this->entryTimeTrackerList[$closestSlotIndex] = $this->entryTime->format('Y-m-d h:i:s');
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