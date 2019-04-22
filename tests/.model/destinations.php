<?php // file: dev/package.php

use \Gekko\Model\ModelDescriptor;

$role = $package->model("Domain\\Destination");

// Set the table name for this model
$role->tableName("destinations");

// Create the properties
$role->property("id")
        ->int32()
        ->autoincrement()
        ->key();

$role->property("name")
        ->string()
        ->length(500);

return $role;