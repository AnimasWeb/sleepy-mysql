<?php

namespace SleepyMySQL;

if(!class_exists('SleepyMySQL\SMPermissions')) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SMPermissions.php');
}

class SMPermissionsAllowAll extends SMPermissions {
  public function hasPermission($table, $function) {
    return true;
  }
}