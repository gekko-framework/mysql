<?php // file: dev/package.php

use \Gekko\Model\ModelDescriptor;

$user = $package->model("Domain\\User");

// Set the table name for this model
$user->tableName("users");

// Create the properties
$user->property("id")
        ->int32()
        ->autoincrement()
        ->key();

$user->property("name")
        ->string()
        ->length(500)
        ->unique();

return $user;