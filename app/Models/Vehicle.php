<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    /**
     * Vehicle's size
     */
    public $size;

    /**
     * Vehicle's plate number
     */
    public $plateNumber;

    /**
     * Constructor
     */
    function __construct(string $size, string $plateNumber) 
    {
        $this->size = $size;
        $this->plateNumber = $plateNumber;
    }

    /**
     * Getter for $type
     */
    public function getSize() : string
    {
        return $this->size;
    }

    /**
     * Getter for $plateNumber
     */
    public function getPlateNumber() : string
    {
        return $this->plateNumber;
    }
}
