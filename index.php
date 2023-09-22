<?php
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_error', 1);

use Carbon\Carbon;

// Initalize Parking
// TODO: the system should accept the number of entrance, parking map, and parking lot sizes
$parking = new App\Parking(3, [], []);

// Create vehicles
$vehicle1 = new App\Vehicle('L', 'ABC 1234');
$vehicle2 = new App\Vehicle('S', 'RTY 4563');
$vehicle3 = new App\Vehicle('M', 'DFG 4567');
$vehicle4 = new App\Vehicle('L', 'JKL 4567');
$vehicle5 = new App\Vehicle('M', 'TYB 3473');
$vehicle6 = new App\Vehicle('S', 'PWU 2354');

// Park vehicles
$parking->park($vehicle1, 1, Carbon::now());
$parking->park($vehicle2, 2, Carbon::now());
$parking->park($vehicle3, 3, Carbon::now());
$parking->park($vehicle4, 2, Carbon::now());
$parking->park($vehicle5, 3, Carbon::now());
$parking->park($vehicle6, 1, Carbon::now());

// $parking->unpark(9, Carbon::now()->add('30', 'minutes'));
// $parking->park($vehicle2, 2, Carbon::now()->add('30', 'minutes'));

// $parking->unpark(9, Carbon::now()->add('30', 'minutes')->add('4', 'hours')); // Vehicle2 will have a total of 5 hours
// $parking->unpark(3, Carbon::now()->add('10', 'hours'));