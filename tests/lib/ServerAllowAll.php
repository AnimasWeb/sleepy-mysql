<?php
/**
 *
 * php -S localhost:8083 ServerRoles.php
 *
 */
require dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$db_config = array(
  'server' => 'localhost',
  'database' => 'test',
  'username' => 'root',
  'password' => '',
  'verbose' => false
);

$po = new \SleepyMySQL\SMPermissionsAllowAll();
$arrest = new \SleepyMySQL\SleepyMySQL($db_config, $po);
$arrest->rest();