<?php
namespace App;

class Vehicle {
    public $type;

    /**
     * Constructor
     */
    function __construct($type) {
        $this->type = $type;
    }

    /**
     * Getter for $type
     */
    public function getType() {
        return $this->type;
    }
}