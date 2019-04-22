<?php // file: dev/package.php

use \Gekko\Model\ModelDescriptor;
use \Gekko\Collections\Collection;
use \Gekko\Model\PackageDescriptor;

// Create a package (namespace)
$package = new PackageDescriptor("Tests");

// Define the schema for the DB
$package->schema("mysql_tests");

// We don't want the "Tests" directory
$package->virtual();

$user = require "users.php";
$destination = require "destinations.php";
$rate = require "rates.php";
$booking = require "bookings.php";
$ticket = require "tickets.php";

$rate->belongsTo($destination)
        ->on("destinationId", "id")
        ->asProperty("destination");

$booking->belongsTo($destination)
        ->on("destinationId", "id")
        ->asProperty("destination");

$ticket->belongsTo($booking)
        ->on("bookingId", "id")
        ->asProperty("booking");

$ticket->belongsTo($rate)
        ->on("rateId", "id")
        ->asProperty("rate");

$ticket->belongsTo($user)
        ->on("userId", "id")
        ->asProperty("user");

return $package;