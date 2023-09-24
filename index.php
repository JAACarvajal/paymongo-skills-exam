<?php
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_error', 1);

use Carbon\Carbon;

$parking = new App\ParkingSystem([
    'S' => 20,
    'M' => 60,
    'L' => 100
], 3);

$parking->initializeParking(
    [
        [1, 2, 3], // this array is one slot
        [3, 4, 5], // another slot here...
        [2, 5, 6],
        [4, 7, 3],
        [5, 4, 2],
        [2, 9, 5],
        [3, 4, 4],
        [5, 2, 8],
        [7, 1, 5],
        [4, 3, 1]
    ], 
    [
        'L',
        'M',
        'S',
        'M',
        'L',
        'S',
        'L',
        'M',
        'S',
        'L'
    ]
);

// Create vehicles
$vehicle1 = new App\Vehicle('L', 'ABC 1234');
$vehicle2 = new App\Vehicle('S', 'RTY 4563');
$vehicle3 = new App\Vehicle('M', 'DFG 4567');
$vehicle4 = new App\Vehicle('L', 'JKL 4567');
$vehicle5 = new App\Vehicle('M', 'TYB 3473');
$vehicle6 = new App\Vehicle('S', 'PWU 2354');

// // Park vehicles
$parking->park($vehicle1, 1, Carbon::now());
$parking->park($vehicle2, 2, Carbon::now());
$parking->park($vehicle3, 3, Carbon::now());
$parking->park($vehicle4, 2, Carbon::now());
$parking->park($vehicle5, 3, Carbon::now());
$parking->park($vehicle6, 1, Carbon::now());

// SCENARIOS
/**
 * (a) All types of cars pay the flat rate of 40 pesos for the first three (3) hours;
 */
$parking->unpark(1, Carbon::now()->add('2', 'hours'));

/**
 * (b) The exceeding hourly rate beyond the initial three (3) hours will be charged as follows:
 * 20/hour for vehicles parked in SP;
 * 60/hour for vehicles parked in MP; and
 * 100/hour for vehicles parked in LP
 */
// $parking->unpark(9, Carbon::now()->add('4', 'hours')); // S
// $parking->unpark(4, Carbon::now()->add('4', 'hours')); // M
// $parking->unpark(1, Carbon::now()->add('4', 'hours')); // L


/**
 * For parking that exceeds 24 hours, every full 24-hour chunk is charged 5,000 pesos regardless of the parking slot.
 * The remainder hours are charged using the method explained in (b).
 */
// $parking->unpark(1, Carbon::now()->add('45', 'hours'));
// $parking->unpark(3, Carbon::now()->add('50', 'hours'));

/**
 * (c) A vehicle leaving the parking complex and returning within one hour based on their exit time must be charged a continuous rate,
 * i.e. the vehicle must be considered as if it did not leave. 
 * Otherwise, rates must be implemented as described. 
 * For example, if a vehicle exits at 10:00 and returns at 10:30, the continuous rate must apply.
 */
// $parking->unpark(1, Carbon::now()->add('30', 'minutes')); // leave within 1 hour
// $parking->park($vehicle1, 2, Carbon::now()->add('45', 'minutes')); // Go back after 15 min
// $parking->unpark(1, Carbon::now()->add('1', 'hours')); // Leave again

