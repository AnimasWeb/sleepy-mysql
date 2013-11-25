<?php

require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

class SMPermissionsAdminTest extends PHPUnit_Framework_TestCase {
  public function testCreatePermissionsDefaults() {
    $p = new \SleepyMySQL\SMPermissionsAdmin();
    $this->assertInstanceOf('\SleepyMySQL\SMPermissions', $p);
    $this->assertAttributeEquals(false, 'admin', $p);
    $this->assertAttributeEquals(\SleepyMySQL\SMPermissionsAdmin::MODE_ALLOW, 'mode', $p);
    $this->assertAttributeEquals(array(), 'permissions', $p);
  }

  public function testCreatePermissionsWithAdminFalse() {
    $p = new \SleepyMySQL\SMPermissionsAdmin(false);
    $this->assertAttributeEquals(false, 'admin', $p);
  }

  public function testCreatePermissionsWithAdminTrue() {
    $p = new \SleepyMySQL\SMPermissionsAdmin(true);
    $this->assertAttributeEquals(true, 'admin', $p);
  }

  public function testCreatePermissionsWithModeAllow() {
    $p = new \SleepyMySQL\SMPermissionsAdmin(false, \SleepyMySQL\SMPermissionsAdmin::MODE_ALLOW);
    $this->assertAttributeEquals(\SleepyMySQL\SMPermissionsAdmin::MODE_ALLOW, 'mode', $p);
  }

  public function testCreatePermissionsWithModeDeny() {
    $p = new \SleepyMySQL\SMPermissionsAdmin(false, \SleepyMySQL\SMPermissionsAdmin::MODE_DENY);
    $this->assertAttributeEquals(\SleepyMySQL\SMPermissionsAdmin::MODE_DENY, 'mode', $p);
  }

  public function testCreatePermissionsWithPermissions() {
    $perms = array('table_name'=>'rw');
    $p = new \SleepyMySQL\SMPermissionsAdmin(false, \SleepyMySQL\SMPermissionsAdmin::MODE_ALLOW, $perms);
    $this->assertAttributeEquals($perms, 'permissions', $p);
  }

  /**
   * @dataProvider provider
   */
  public function testPermissions($admin, $mode, $perms, $table, $function, $expected) {
    $p = new \SleepyMySQL\SMPermissionsAdmin($admin, $mode, $perms);
    $this->assertAttributeEquals($perms, 'permissions', $p);
    $this->assertEquals($expected, $p->hasPermission($table, $function));
  }

  public function provider() {
    $table = 'table_name';
    $perms_e = array($table => '');
    $perms_r = array($table => 'r');
    $perms_w = array($table => 'w');
    $perms_rw = array($table => 'rw');
    $read = \SleepyMySQL\SMPermissions::TABLE_READ;
    $write = \SleepyMySQL\SMPermissions::TABLE_WRITE;
    $allow = \SleepyMySQL\SMPermissionsAdmin::MODE_ALLOW;
    $deny = \SleepyMySQL\SMPermissionsAdmin::MODE_DENY;
    $user = false;
    $admin = true;

    return array(
      array($user, $allow, array(), $table, $read, true),
      array($user, $allow, array(), $table, $write, true),
      array($user, $allow, $perms_e, $table, $read, false),
      array($user, $allow, $perms_e, $table, $write, false),
      array($user, $allow, $perms_r, $table, $read, true),
      array($user, $allow, $perms_r, $table, $write, false),
      array($user, $allow, $perms_w, $table, $read, false),
      array($user, $allow, $perms_w, $table, $write, true),
      array($user, $allow, $perms_rw, $table, $read, true),
      array($user, $allow, $perms_rw, $table, $write, true),
      array($admin, $allow, array(), $table, $read, true),
      array($admin, $allow, array(), $table, $write, true),
      array($admin, $allow, $perms_e, $table, $read, true),
      array($admin, $allow, $perms_e, $table, $write, true),
      array($admin, $allow, $perms_r, $table, $read, true),
      array($admin, $allow, $perms_r, $table, $write, true),
      array($admin, $allow, $perms_w, $table, $read, true),
      array($admin, $allow, $perms_w, $table, $write, true),
      array($admin, $allow, $perms_rw, $table, $read, true),
      array($admin, $allow, $perms_rw, $table, $write, true),
      array($user, $deny, array(), $table, $read, false),
      array($user, $deny, array(), $table, $write, false),
      array($user, $deny, $perms_e, $table, $read, false),
      array($user, $deny, $perms_e, $table, $write, false),
      array($user, $deny, $perms_r, $table, $read, true),
      array($user, $deny, $perms_r, $table, $write, false),
      array($user, $deny, $perms_w, $table, $read, false),
      array($user, $deny, $perms_w, $table, $write, true),
      array($user, $deny, $perms_rw, $table, $read, true),
      array($user, $deny, $perms_rw, $table, $write, true),
      array($admin, $deny, array(), $table, $read, true),
      array($admin, $deny, array(), $table, $write, true),
      array($admin, $deny, $perms_e, $table, $read, true),
      array($admin, $deny, $perms_e, $table, $write, true),
      array($admin, $deny, $perms_r, $table, $read, true),
      array($admin, $deny, $perms_r, $table, $write, true),
      array($admin, $deny, $perms_w, $table, $read, true),
      array($admin, $deny, $perms_w, $table, $write, true),
      array($admin, $deny, $perms_rw, $table, $read, true),
      array($admin, $deny, $perms_rw, $table, $write, true),
    );
  }
}