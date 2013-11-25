<?php

namespace SleepyMySQL;

if(!class_exists('SleepyMySQL\SMPermissions')) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SMPermissions.php');
}

class SMPermissionsRoles extends SMPermissions {
  /**
   * Array of permissions
   * @var array
   */
  private $permissions;
  /**
   * Array of roles for a user
   * @var array
   */
  private $roles;
  /**
   * @var string
   */
  private $adminRole;
  /**
   * @var boolean
   */
  private $isAdmin;

  function __construct($permissions, $roles=null, $admin_role='admin') {
    $this->permissions = $this->cleanPermissions($permissions);
    $roles = ($roles !== null) ? $roles : array();
    $this->roles = $this->cleanRoles($roles);
    $this->adminRole = strtolower($admin_role);
    $this->setIsAdmin();
  }

  public function hasPermission($table, $function) {
    if($this->isAdmin) return true;

    $table = strtolower($table);

    if(array_key_exists($table, $this->permissions)) {
      $keys = array_keys($this->permissions[$table]);
      $roles = array_intersect($keys, $this->roles);
      foreach($roles as $role) {
        $perms = $this->permissions[$table][$role];
        if(stripos($perms, $function) !== false) {
          return true;
        }
      }
    }
    return false;
  }

  public function setPermissions($permissions) {
    $this->permissions = $permissions;
  }

  public function setRoles($roles) {
    $this->roles = $this->cleanRoles($roles);
    $this->setIsAdmin();
  }

  protected function cleanPermissions($permissions) {
    $new = array();
    foreach($permissions as $table => $roles) {
      $table = strtolower($table);
      $new[$table] = array();
      foreach($roles as $role => $perms) {
        $new[$table][strtolower($role)] = $perms;
      }
    }
    return $new;
  }

  protected function cleanRoles($roles) {
    $new = array();
    foreach($roles as $role) {
      array_push($new, strtolower($role));
    }
    return $new;
  }

  protected function setIsAdmin() {
    $this->isAdmin = in_array($this->adminRole, $this->roles);
  }
}