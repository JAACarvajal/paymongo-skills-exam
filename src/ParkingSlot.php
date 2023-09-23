<?php

namespace App;

class ParkingSlot {
    public $distance;
    public $size;
    public $isOccupied;

    /**
     * Constructor
     * @param $distance
     * @param $size
     */
    function __construct(array $distance, string $size)
    {
        $this->distance = $distance;
        $this->size = $size;
    }

    /**
     * Getter for distance
     * @param $index
     */
    public function getDistance($index = null) {
        return $index = null ? $this->distance : $this->distance[$index];
    }

    /**
     * Getter for size
     */
    public function getSize() {
        return $this->size;
    }
}