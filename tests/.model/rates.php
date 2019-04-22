<?php // file: dev/package.php

use \Gekko\Model\ModelDescriptor;

$rate = $package->model("Domain\\Rate");

// Set the table name for this model
$rate->tableName("rates");

// Create the properties
$rate->property("id")
        ->int32()
        ->autoincrement()
        ->key();

$rate->property("destinationId")
        ->int32();

$rate->property("name")
        ->varchar()
        ->length(10);

$rate->property("amount")
        ->decimal();

return $rate;