<?php

require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

class SMPermissionsAllowAllTest extends PHPUnit_Framework_TestCase {
  public function testCreatePermissions() {
    $p = new \SleepyMySQL\SMPermissionsAllowAll();
    $this->assertInstanceOf('\SleepyMySQL\SMPermissions', $p);
  }

  public function testHasPermission() {
    $p = new \SleepyMySQL\SMPermissionsAllowAll();
    $this->assertTrue($p->hasPermission('', \SleepyMySQL\SMPermissions::TABLE_READ));
    $this->assertTrue($p->hasPermission('', \SleepyMySQL\SMPermissions::TABLE_WRITE));
  }
}