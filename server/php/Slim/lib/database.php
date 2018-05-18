<?php
use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;
$capsule->addConnection(array(
    'driver' => R_DB_DRIVER,
    'host' => R_DB_HOST,
    'database' => R_DB_NAME,
    'username' => R_DB_USER,
    'password' => R_DB_PASSWORD,
    'port' => R_DB_PORT,
    'charset' => 'utf8',
    'collation' => 'utf8_general_ci',
    'prefix' => ''
));
$capsule->setAsGlobal();
$capsule->bootEloquent();
