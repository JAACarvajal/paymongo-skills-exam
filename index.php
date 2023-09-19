<?php
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_error', 1);

// Initalize Parking
$parking = new App\Parking();

// Create vehicles
$vehicle1 = new App\Vehicle('L');
$vehicle2 = new App\Vehicle('M');
$vehicle3 = new App\Vehicle('S');


$parking->park($vehicle1);
$parking->park($vehicle2);
$parking->park($vehicle3);
$parking->park($vehicle4);