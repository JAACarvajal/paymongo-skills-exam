<?php
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_error', 1);

use Carbon\Carbon;

// Initalize Parking
$parking = new App\Parking();

// Create vehicles
$vehicle1 = new App\Vehicle('L', 'ABC1234');
$vehicle2 = new App\Vehicle('M', 'RTY4563');
$vehicle3 = new App\Vehicle('S', 'DFG4567');
$vehicle4 = new App\Vehicle('M', 'JKL4567');
$vehicle5 = new App\Vehicle('L', 'TYB3473');
$vehicle6 = new App\Vehicle('S', 'PWU2354');

// Park vehicles
$parking->park($vehicle1, 1, Carbon::now());
$parking->park($vehicle2, 2, Carbon::now());
$parking->park($vehicle3, 3, Carbon::now());
$parking->park($vehicle4, 2, Carbon::now());
$parking->park($vehicle5, 3, Carbon::now());
$parking->park($vehicle6, 1, Carbon::now());

$parking->unpark(0, Carbon::now()->add(3, 'hours')->add('30', 'minutes'));

// echo '<pre>'; print_r($parking->getTakenSlots()); echo '</pre>';
// echo '<pre>'; print_r($parking->getEntryTimeTrackerList()); echo '</pre>';