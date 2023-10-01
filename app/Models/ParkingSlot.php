<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingSlot extends Model
{
/**
     * Parking lot distance from all entrance
     */
    public $distance;

    /**
     * Parking lot size
     */
    public $size;

    /**
     * Constructor
     * 
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
     * 
     * @param $index
     */
    public function getDistance($index = null) : int | array
    {
        return $index = null ? $this->distance : $this->distance[$index];
    }

    /**
     * Getter for size
     * 
     * @return string
     */
    public function getSize() : string
    {
        return $this->size;
    }
}
