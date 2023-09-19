<?php

namespace App;

class Parking {
    public $vehicleTypes = ['S', 'M', 'L'];
    public $parkingTypes = ['SP', 'MP', 'LP'];

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
    public $map = [
        [1, 2, 3], // this array is ONE SLOT
        [3, 4, 5], // ANOTHER SLOT here...
        [2, 5, 6],
    ];
    /**
     * The sizes of every corresponding parking slot. Again, you are welcome to introduce your own design. 
     * We suggest using a list of corresponding sizes described in integers: [0, 2, 1, 1, ...] where 0, 1, 2 means small, medium, and large in that order. 
     * Another useful design may be a dictionary of parking sizes with corresponding slots as values.
     */
    public $parkingSlotSize = [
        'LP',
        'MP',
        'SP'
    ];

    /**
     * Constructor
     */
    function __construct($vehicleTypes = null, $parkingTypes = null) {
        if ($vehicleTypes !== null) {
            $this->vehicleTypes = $vehicleTypes;
        }

        if ($parkingTypes !== null) {
            $this->parkingTypes = $parkingTypes;
        }
    }

    /**
     * Park vehicle
     * A vehicle must be assigned a possible and available slot closest to the parking entrance
     */
    public function park($vehicle, ) {
        $vehicleType = $vehicle->getType();
        switch ($vehicleType) {
            // Small vehicles
            case $this->vehicleTypes[0]:
                // if () {
                    
                // }
                break;
            // Meduim vehicles
            case $this->vehicleTypes[1]:
                echo 'Medium car';
                break;
            // Large vehicles
            case $this->vehicleTypes[2]:
                echo 'Chonky car';
                break;
            default:
                echo 'Vehicle type is invalid';
                break;
        }
    }

    /**
     * Calculate fee of a vehicle
     */
    private function calculateFee() {

    }

    /**
     * Find the closest parking slot
     */
    private function findClosestSlot() {

    }

    /**
     * Getter for $type
     */
    public function getType() {
        return $this->type;
    }
}