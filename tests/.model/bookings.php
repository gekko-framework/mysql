<?php // file: dev/package.php

use \Gekko\Model\ModelDescriptor;

$post = $package->model("Domain\\Booking");

// Set the table name for this model
$post->tableName("bookings");

// Create the properties
$post->property("id")
        ->int32()
        ->autoincrement()
        ->key();
        
$post->property("destinationId")
        ->column("destination_id")
        ->int32();

$post->property("name")
        ->text();



return $post;