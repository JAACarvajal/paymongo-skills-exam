<?php
namespace App;

class Vehicle {
    public $type;
    public $plateNumber;

    /**
     * Constructor
     */
    function __construct($type, $plateNumber) {
        $this->type = $type;
        $this->plateNumber = $plateNumber;
    }

    /**
     * Getter for $type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Getter for $type
     */
    public function getPlateNumber() {
        return $this->plateNumber;
    }
}