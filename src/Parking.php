<?php

namespace App;

class Parking {
    public $vehicleTypes = ['S', 'M', 'L'];
    public $parkingTypes = ['SP', 'MP', 'LP'];

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
     */
    public function park($vehicles) {
        $vehicleType = $vehicles->getType();
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
     * Getter for $type
     */
    public function getType() {
        return $this->type;
    }
}