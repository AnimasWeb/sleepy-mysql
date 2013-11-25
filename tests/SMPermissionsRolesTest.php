<?php

require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

class SMPermissionsRolesTest extends PHPUnit_Framework_TestCase {
  public function testCleaningPermissionsAndRoles() {
    $perms = array(
      'table1' => array(
        'roleRW' => 'rw',
        'roleR' => 'r',
        'roLew' => 'w'
      )
    );
    $perms_e = array(
      'table1' => array(
        'rolerw' => 'rw',
        'roler' => 'r',
        'rolew' => 'w'
      )
    );
    $roles = array('Admin', 'role1', 'RoLe2');
    $roles_e = array('admin', 'role1', 'role2');

    $p = new \SleepyMySQL\SMPermissionsRoles($perms, $roles);
    $this->assertAttributeEquals($perms_e, 'permissions', $p);
    $this->assertAttributeEquals($roles_e, 'roles', $p);
  }

  /**
   * @dataProvider userSingleRoleProvider
   */
  public function testUserSingleRole($table, $roles, $function, $expected) {
    $perms = array(
      'table1' => array(
        'rolerw' => 'rw',
        'roler' => 'r',
        'rolew' => 'w'
      )
    );
    $p = new \SleepyMySQL\SMPermissionsRoles($perms, $roles);
    $this->assertEquals($expected, $p->hasPermission($table, $function));
  }

  public function userSingleRoleProvider() {
    $read = \SleepyMySQL\SMPermissions::TABLE_READ;
    $write = \SleepyMySQL\SMPermissions::TABLE_WRITE;
    return array(
      array('table1', array('rolerw'), $read, true),
      array('table1', array('rolerw'), $write, true),
      array('table1', array('roler'), $read, true),
      array('table1', array('roler'), $write, false),
      array('table1', array('rolew'), $read, false),
      array('table1', array('rolew'), $write, true),
      array('table1', array('rolen'), $read, false),
      array('table1', array('rolen'), $write, false),
      array('table2', array('rolerw'), $read, false),
      array('table2', array('rolerw'), $write, false),
      array('table2', array('roler'), $read, false),
      array('table2', array('roler'), $write, false),
      array('table2', array('rolew'), $read, false),
      array('table2', array('rolew'), $write, false),
      array('table2', array('rolen'), $read, false),
      array('table2', array('rolen'), $write, false),
    );
  }

  public function testAdminRole() {
    $perms = array(
      'table1' => array(
        'rolerw' => 'rw',
        'roler' => 'r',
        'rolew' => 'w'
      ),
      'table2' => array(
        'admin' => ''
      )
    );
    $p = new \SleepyMySQL\SMPermissionsRoles($perms, array('admin'));
    $this->assertTrue($p->hasPermission('table1', \SleepyMySQL\SMPermissions::TABLE_READ));
    $this->assertTrue($p->hasPermission('table1', \SleepyMySQL\SMPermissions::TABLE_WRITE));
    $this->assertTrue($p->hasPermission('table2', \SleepyMySQL\SMPermissions::TABLE_READ));
    $this->assertTrue($p->hasPermission('table2', \SleepyMySQL\SMPermissions::TABLE_WRITE));
    $this->assertTrue($p->hasPermission('table3', \SleepyMySQL\SMPermissions::TABLE_READ));
    $this->assertTrue($p->hasPermission('table3', \SleepyMySQL\SMPermissions::TABLE_WRITE));
  }

  public function testAlternateAdminName() {
    $p1 = new \SleepyMySQL\SMPermissionsRoles(array(), array('admin'), 'newadmin');
    $p2 = new \SleepyMySQL\SMPermissionsRoles(array(), array('newadmin'), 'newadmin');

    $this->assertAttributeEquals(false, 'isAdmin', $p1); // Test that default is not working
    $this->assertAttributeEquals(true, 'isAdmin', $p2); // Test that new role is working
  }

  public function testPermissionsChange() {
    $perms1 = array(
      'table1' => array(
        'rolerw' => 'rw',
      )
    );
    $perms2 = array(
      'table1' => array(
        'rolerw' => 'rw',
        'roler' => 'r',
        'rolew' => 'w'
      )
    );

    $p = new \SleepyMySQL\SMPermissionsRoles($perms1);
    $this->assertAttributeEquals($perms1, 'permissions', $p);
    $p->setPermissions($perms2);
    $this->assertAttributeEquals($perms2, 'permissions', $p);
  }

  public function testRoleChange() {
    $p = new \SleepyMySQL\SMPermissionsRoles(array(), array('Role1'));
    $this->assertAttributeEquals(array('role1'), 'roles', $p);
    $this->assertAttributeEquals(false, 'isAdmin', $p);

    $p->setRoles(array('Admin'));
    $this->assertAttributeEquals(array('admin'), 'roles', $p);
    $this->assertAttributeEquals(true, 'isAdmin', $p);
  }
}