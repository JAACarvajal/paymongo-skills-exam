<?php

namespace App;

use Carbon\Carbon;
use App\ParkingSlot;
use App\Vehicle;
use Exception;

class ParkingSystem {
    // CONSTANTS
    protected const FLAT_RATE = 40;
    protected const FLAT_RATE_TOTAL_HOURS = 3;
    protected const HOURS_PER_DAY = 24;
    protected const FULL_DAY_CHARGE_RATE = 5000;

    /**
     * List of taken slots
     */
    public $takenSlots = [];

    /**
     * List of parking history
     */
    public $parkingHistory = [];

    /**
     * Parking slot size list
     */
    public $parkingSlotSizeList;

    /**
     * Vehicle
     */
    public $vehicle;

    /**
     * Vehicle entry time
     */
    public $entryTime;

    /**
     * Parking map
     */
    public $parkingMap;

    /**
     * Parking slot sizes/types of slots in parkingMap 
     */
    public $parkingSlotSizes;

    /**
     * Number of entrance to the parking lot
     */
    public $numberOfEntrance = 3;

    /**
     * Number of entrance to the parking lot
     */
    public $additionalChargePerHour;

    /**
     * Constructor
     * @param $parkingSlotSizeList
     * @param $numberOfEntrance
     */
    function __construct(array $parkingSlotSizeList, int $numberOfEntrance = 3)
    {
        $this->additionalChargePerHour = $parkingSlotSizeList;
        $this->parkingSlotSizeList = array_keys($parkingSlotSizeList);
        $this->numberOfEntrance = $numberOfEntrance;
    }

    /**
     * Getter for $takenSlots
     */
    public function getTakenSlots() : array
    {
        return $this->takenSlots;
    }

    /**
     * Getter for $parkingHistory
     */
    public function getParkingHistory() : array
    {
        return $this->parkingHistory;
    }

    /**
     * Initialize parking map
     * @param $parkingMap
     * @param $parkingSlotSizes
     */
    public function initializeParking(array $parkingMap, array $parkingSlotSizes) : void
    {
        try {
            // Check if parking slot sizes are valid
            foreach ($parkingSlotSizes as $index => $parkingSlotSize) {
                if (in_array($parkingSlotSize, $this->parkingSlotSizeList) === false) {
                    throw new Exception('Invalid parking size.');
                }
            }

            // Check if count of parking slot sizes matches the count of parking slots available
            if (count($parkingMap) !== count($parkingSlotSizes)) {
                throw new Exception('The count of parking slot sizes does not match the count of parking slots available.');
            }

            // Check if parking slots are valid
            foreach ($parkingMap as $index => $parkingSlotDistance) {
                $parkingSlotEntryDistanceCount = count($parkingSlotDistance);
                if ($parkingSlotEntryDistanceCount !== $this->numberOfEntrance || $parkingSlotEntryDistanceCount < 3) {
                    throw new Exception('Invalid parking slot.');
                }

                $this->parkingMap[] = new ParkingSlot($parkingSlotDistance, $parkingSlotSizes[$index]);
            }

            print "Successfully created parking map. \r\n";
        } catch (Exception $error) {
            print $error->getMessage() . "\r\n";
        }
    }

    /**
     * Park vehicle
     * @param $vehicle
     * @param $entrance
     * @param $entryTime
     */
    public function park(Vehicle $vehicle, int $entrance, Carbon $entryTime) : void 
    { 
        $this->vehicle = $vehicle;
        $this->entryTime = $entryTime;

        $closestSlotDistance = PHP_INT_MAX; // Initially set the parking slot distance to max value
        $closestSlotIndex = null; // Closest slot array index

        foreach ($this->parkingMap as $index => $parkingSlot) {
            // Get the distance from the entrance
            $parkingSlotDistanceFromEntrance = $parkingSlot->getDistance($entrance - 1);

            /**
             * Check if the parking slot if closer than the previous AND
             * If the slot is not in yet taken AND
             * If the vehicle type is compatible with the parking slot
             */
            if (
                $parkingSlotDistanceFromEntrance < $closestSlotDistance &&
                array_key_exists($index, $this->takenSlots) === false &&
                $this->checkVehicleCompatibility($this->parkingMap[$index]) === true
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
        print "Vehicle " . $this->vehicle->getPlateNumber() . " (" . $this->vehicle->getSize() .  ") entered and was not able to park. \r\n";
    }

    /**
     * Unpark vehicle
     * @param $parkingSlot
     * @param $exitTime
     */
    public function unpark(int $parkingSlot, Carbon $exitTime) : void 
    {
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
    private function calculateFee(int $totalTime, int $parkingSlotIndex) : int
    {
        $totalParkingFee = self::FLAT_RATE; // Set initial fee to flat rate
        $additionalFeePerHour = $this->additionalChargePerHour[$this->parkingMap[$parkingSlotIndex]->getSize()]; // Get the additional change
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
    private function assignSlot(int $closestSlotIndex) : void 
    {
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
            'vehicle'    => $this->vehicle,
            'entry_time' => $entryTime,
        ];

        print "Vehicle " . $this->vehicle->getPlateNumber() . " (" . $this->vehicle->getSize() . ") entered at " . $this->entryTime->format('h:i:s') . " and parked at slot " . $closestSlotIndex + 1 . " (" . $this->parkingMap[$closestSlotIndex]->getSize() . ") \r\n";
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
     * Print data after on vehicle exit
     * @param $parkingSlotIndex
     * @param $totalParkingFee
     */
    private function print(int $parkingSlotIndex, int $totalParkingFee) : void 
    {
        print "\r\n";
        print "EXIT \r\n";
        print 'Vehicle: ' . $this->takenSlots[$parkingSlotIndex]['vehicle']->getPlateNumber() . "\r\n";
        print 'Parking Slot: ' . $parkingSlotIndex + 1 . "\r\n";
        print 'Entry Time: ' . $this->parkingHistory[$this->vehicle->getPlateNumber()]['entry_time']->toDayDateTimeString() . "\r\n";
        print 'Exit Time: ' . $this->parkingHistory[$this->vehicle->getPlateNumber()]['exit_time']->toDayDateTimeString() . "\r\n";
        print 'Total parking fee: ' . $totalParkingFee . "\r\n";
    }
}