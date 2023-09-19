<?php
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_error', 1);

use Carbon\Carbon;

// Initalize Parking
$parking = new App\Parking();

// Create vehicles
$vehicle1 = new App\Vehicle('L');
$vehicle2 = new App\Vehicle('M');
$vehicle3 = new App\Vehicle('S');
$vehicle4 = new App\Vehicle('M');
$vehicle5 = new App\Vehicle('L');
$vehicle6 = new App\Vehicle('S');


$parking->park($vehicle1, 1, Carbon::now());
$parking->park($vehicle2, 2, Carbon::now());
$parking->park($vehicle3, 3, Carbon::now());
$parking->park($vehicle4, 2, Carbon::now());
$parking->park($vehicle5, 3, Carbon::now());
$parking->park($vehicle6, 1, Carbon::now());

$parking->unpark(0, 1, Carbon::now());

// echo '<pre>'; print_r($parking->getTakenSlots()); echo '</pre>';
// echo '<pre>'; print_r($parking->getEntryTimeTrackerList()); echo '</pre>';