<?php // file: dev/package.php

use \Gekko\Model\ModelDescriptor;

$ticket = $package->model("Domain\\Ticket");

// Set the table name for this model
$ticket->tableName("tickets");

// Create the properties
$ticket->property("id")
        ->column("id")
        ->int32()
        ->autoincrement()
        ->key();

$ticket->property("bookingId")
        ->column("booking_id")
        ->int32();

$ticket->property("rateId")
        ->column("rate_id")
        ->int32();

$ticket->property("userId")
        ->column("user_id")
        ->int32()
        ->nullable();

return $ticket;