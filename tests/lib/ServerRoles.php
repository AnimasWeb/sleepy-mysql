<?php
/**
 *
 * php -S localhost:8082 ServerRoles.php
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

$options = json_decode(urldecode($_COOKIE['myoptions']), true);

$po = new \SleepyMySQL\SMPermissionsRoles($options['permissions'], $options['roles']);
$sm = new \SleepyMySQL\SleepyMySQL($db_config, $po);
$sm->rest();