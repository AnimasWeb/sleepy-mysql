<?php

namespace SleepyMySQL;

if(!class_exists('SleepyMySQL\SMPermissions')) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SMPermissions.php');
}

class SMPermissionsAdmin extends SMPermissions {
  const MODE_ALLOW = 1;
  const MODE_DENY = 2;

  private $admin;
  private $mode;
  private $permissions;

  function __construct($admin=false, $mode=1, $permissions=null) {
    $this->admin = $admin;
    $this->mode = $mode;
    if(!is_array($permissions)) {
      $permissions = array();
    }
    $this->permissions = $permissions;
  }

  public function setAdmin($status) {
    $this->admin = $status;
  }

  public function hasPermission($table, $function) {
    return ($this->admin === true)
        || ($this->mode == self::MODE_ALLOW && $this->checkModeAllow($table, $function))
        || ($this->mode == self::MODE_DENY && $this->checkModeDeny($table, $function));
  }

  protected function checkModeAllow($table, $function) {
    return !array_key_exists($table, $this->permissions) || $this->hasFunction($table, $function);
  }

  protected function checkModeDeny($table, $function) {
    return $this->hasFunction($table, $function);
  }

  protected function hasFunction($table, $function) {
    return array_key_exists($table, $this->permissions)
        && stripos($this->permissions[$table], $function) !== false;
  }
}