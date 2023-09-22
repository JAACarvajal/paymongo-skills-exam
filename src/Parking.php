<?php

namespace App;

use Carbon\Carbon;

class Parking {
    public const FLAT_RATE = 40;
    public const FLAT_RATE_TOTAL_HOURS = 3;
    public const HOURS_PER_DAY = 24;
    public const FULL_DAY_CHARGE_RATE = 5000;
    public const ADDITIONAL_CHARGES_PER_HOUR = [
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
        [1, 2, 3], // this array is one slot
        [3, 4, 5], // another slot here...
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
        'L', // size for $parkingMap[0]
        'M', // size for $parkingMap[1]
        'S',
        'M',
        'L',
        'S',
        'L',
        'M',
        'S',
        'L'
    ];

    /**
     * Number of entrance to the parking lot
     */
    public $numberOfEntrance;

    /**
     * Constructor
     * @param $numberOfEntrance
     * @param $parkingMap
     * @param $parkingSlotSizes
     */
    function __construct($numberOfEntrance, $parkingMap, $parkingSlotSizes) {
        $this->numberOfEntrance = $numberOfEntrance;
        $this->parkingMap = $parkingMap;
        $this->parkingSlotSizes = $parkingSlotSizes;
      }

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
     * Print data after on vehicle exit
     * @param $parkingSlotIndex
     * @param $totalParkingFee
     */
    public function print($parkingSlotIndex, $totalParkingFee) {
        print "\r\n";
        print "EXIT \r\n";
        print 'Vehicle: ' . $this->takenSlots[$parkingSlotIndex]['vehicle']->getPlateNumber() . "\r\n";
        print 'Parking Slot: ' . $parkingSlotIndex + 1 . "\r\n";
        print 'Entry Time: ' . $this->parkingHistory[$this->vehicle->getPlateNumber()]['entry_time']->toDayDateTimeString() . "\r\n";
        print 'Exit Time: ' . $this->parkingHistory[$this->vehicle->getPlateNumber()]['exit_time']->toDayDateTimeString() . "\r\n";
        print 'Total parking fee: ' . $totalParkingFee . "\r\n";
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
        $parkingSlotIndex = $parkingSlot - 1; // Get parking slot index
        $this->vehicle = $this->takenSlots[$parkingSlotIndex]['vehicle']; // Set the vehicle

        // Check if a vehice is parked on the parking slot
        if (array_key_exists($parkingSlotIndex, $this->takenSlots) === false) {
            print 'No vehicle parked at slot ' . $parkingSlot . "\r\n";
            return;
        }

        // Calculate the parking total time, round up always (2.3 => 3, 3.4 => 4)
        $totalTime = ceil(round($this->takenSlots[$parkingSlotIndex]['entry_time']->floatDiffInHours($exitTime), 2));
        
        // Calculate the parking fee
        $totalParkingFee = $this->calculateFee($totalTime, $parkingSlotIndex);

        // Record parking
        $this->parkingHistory[$this->vehicle->getPlateNumber()] = [
            'entry_time' => $this->takenSlots[$parkingSlotIndex]['entry_time'],
            'exit_time'  => $exitTime
        ];

        // Print data
        $this->print($parkingSlotIndex, $totalParkingFee);

        // Remove the slot from $takenSlots
        unset($this->takenSlots[$parkingSlotIndex]);
    }

    /**
     * Calculate fee of a vehicle
     * @param $totalTime
     * @param $parkingSlotType
     */
    private function calculateFee($totalTime, $parkingSlotIndex) {
        $totalParkingFee = self::FLAT_RATE; // Set initial fee to flat rate
        $additionalFeePerHour = self::ADDITIONAL_CHARGES_PER_HOUR[$this->parkingSlotSizes[$parkingSlotIndex]]; // Get the additional change
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
     * Find the closest parking slot
     */
    private function findClosestSlot() {
        $closestSlotDistance = PHP_INT_MAX; // Initially set the parking slot distance to max value
        $closestSlotIndex = null; // Closest slot array index

        foreach ($this->parkingMap as $index => $parkingSlotArray) {
            // Get the distance from the entrance
            $parkingSlotDistanceFromEntrance = $parkingSlotArray[$this->entrance - 1]; 

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
        
        // If there is an available slot
        if ($closestSlotIndex !== null) {
            $this->assignSlot($closestSlotIndex);
            return;
        }
        
        // If there are no available slots
        print "Vehicle " . $this->vehicle->getPlateNumber() . " (" . $this->vehicle->getType() .  ") entered from Entrance $this->entrance and was not able to park. \r\n";
    }

    /**
     * Assign a parking slot
     * @param $closestSlotIndex
     * @param $hasParkHistory
     */
    private function assignSlot($closestSlotIndex, $hasParkHistory = false) {
        $entryTime = $this->entryTime;

        /**
         * If vehicle has parking history AND
         * if vehicle left the parking complex and returned within one hour 
         */
        if (
            array_key_exists($this->vehicle->getPlateNumber(), $this->parkingHistory) === true &&
            $this->entryTime->floatDiffInHours($this->parkingHistory[$this->vehicle->getPlateNumber()]['exit_time']) <= 1
        ) {
            $entryTime = $this->parkingHistory[$this->vehicle->getPlateNumber()]['entry_time'];
        }

        // Push data to taken slots
        $this->takenSlots[$closestSlotIndex] = [
            'vehicle'           => $this->vehicle,
            'entry_time'        => $entryTime,
            'parking_slot_type' => $this->parkingSlotSizes[$closestSlotIndex],
            'entrance'          => $this->entrance,
        ];

        print "Vehicle " . $this->vehicle->getPlateNumber() . " (" . $this->vehicle->getType() . ") entered from Entrance $this->entrance at " . $this->entryTime->format('h:i:s') . " and parked at slot " . $closestSlotIndex + 1 . " (" . $this->parkingSlotSizes[$closestSlotIndex] . ") \r\n";
    }

    /**
     * Returns true if vehicle is compatible with the parking slot
     * @param $index
     */
    private function seeVehicleCompatibility($index) {
        $vehicleType = $this->vehicle->getType();
        $parkingSlotType = $this->parkingSlotSizes[$index];

        return (
            $vehicleType === 'S' ||
            ($vehicleType === 'M' && ($parkingSlotType === 'M' || $parkingSlotType === 'L')) ||
            ($vehicleType === 'L' && $parkingSlotType === 'L')
        );
    }
}