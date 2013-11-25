<?php

if(!class_exists('FixtureTestCase')) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'FixtureTestCase.php');
}

class SleepyMySQLPermissionsRolesTest extends FixtureTestCase {

  public $fixtures = array(
    'posts',
    'postmeta',
    'options'
  );

  public function testServerUp() {
    $connection = @fsockopen('localhost', '8082');
    $this->assertTrue(is_resource($connection), 'Run in "tests/lib": php -S localhost:8082 ServerRoles.php');
    fclose($connection);
  }

  /**
   * @depends testServerUp
   * @dataProvider provider
   */
  public function testPermissions($crud, $roles, $table, $expected) {
    $permissions = array(
      'posts' => array(
        'role1' => 'rw',
        'role2' => 'r',
        'role3' => 'w',
        'role4' => '',
      )
    );

    $curl = $this->curl($crud, $permissions, $roles, $table);
    $code = $curl['code'];
    //error_log(var_export($curl['headers'], true));
    $this->assertEquals($expected, $code);
  }

  public function provider() {
    return array(
      array('c', array('role1'), 'posts', 204),
      array('r', array('role1'), 'posts', 200),
      array('u', array('role1'), 'posts', 404),
      array('d', array('role1'), 'posts', 404),
      array('c', array('role2'), 'posts', 403),
      array('r', array('role2'), 'posts', 200),
      array('u', array('role2'), 'posts', 403),
      array('d', array('role2'), 'posts', 403),
      array('c', array('role3'), 'posts', 204),
      array('r', array('role3'), 'posts', 403),
      array('u', array('role3'), 'posts', 404),
      array('d', array('role3'), 'posts', 404),
      array('c', array('role4'), 'posts', 403),
      array('r', array('role4'), 'posts', 403),
      array('u', array('role4'), 'posts', 403),
      array('d', array('role4'), 'posts', 403),
      array('c', array('role5'), 'posts', 403),
      array('r', array('role5'), 'posts', 403),
      array('u', array('role5'), 'posts', 403),
      array('d', array('role5'), 'posts', 403),
      array('c', array('admin'), 'posts', 204),
      array('r', array('admin'), 'posts', 200),
      array('u', array('admin'), 'posts', 404),
      array('d', array('admin'), 'posts', 404),
      array('c', array('role1'), 'posts2', 403),
      array('r', array('role1'), 'posts2', 403),
      array('u', array('role1'), 'posts2', 403),
      array('d', array('role1'), 'posts2', 403),
      array('c', array('role2'), 'posts2', 403),
      array('r', array('role2'), 'posts2', 403),
      array('u', array('role2'), 'posts2', 403),
      array('d', array('role2'), 'posts2', 403),
      array('c', array('role3'), 'posts2', 403),
      array('r', array('role3'), 'posts2', 403),
      array('u', array('role3'), 'posts2', 403),
      array('d', array('role3'), 'posts2', 403),
      array('c', array('role4'), 'posts2', 403),
      array('r', array('role4'), 'posts2', 403),
      array('u', array('role4'), 'posts2', 403),
      array('d', array('role4'), 'posts2', 403),
      array('c', array('role5'), 'posts2', 403),
      array('r', array('role5'), 'posts2', 403),
      array('u', array('role5'), 'posts2', 403),
      array('d', array('role5'), 'posts2', 403),
      array('c', array('admin'), 'posts2', 404),
      array('r', array('admin'), 'posts2', 404),
      array('u', array('admin'), 'posts2', 404),
      array('d', array('admin'), 'posts2', 404),
    );
  }

  private function curl($crud, $permissions, $roles, $table, $id='', $data=array()) {
    $id = (empty($id)) ? '' : "/$id";
    $cookie = urlencode(json_encode(array('permissions'=>$permissions, 'roles'=>$roles)));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8082/$table$id");
    curl_setopt($ch, CURLOPT_COOKIE, "myoptions=$cookie");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    switch($crud) {
      case 'c':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
        break;
      case 'r':
        break;
      case 'u':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
        break;
      case 'd':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;
    }

    curl_setopt($ch, CURLOPT_HEADER, 1);

    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return array(
      'code' => $code,
      'headers' => explode("\r\n", trim(substr($response, 0, $header_size))),
      'body' => substr($response, $header_size)
    );
  }
}