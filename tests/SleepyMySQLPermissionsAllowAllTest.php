<?php

if(!class_exists('FixtureTestCase')) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'FixtureTestCase.php');
}

class SleepyMySQLPermissionsAllowAllTest extends FixtureTestCase {

  public $fixtures = array(
    'posts',
    'postmeta',
    'options'
  );

  public function testServerUp() {
    $connection = @fsockopen('localhost', '8083');
    $this->assertTrue(is_resource($connection), 'Run in "tests/lib": php -S localhost:8083 ServerAllowAll.php');
    fclose($connection);
  }

  /**
   * @depends testServerUp
   * @dataProvider provider
   */
  public function testPermissions($crud, $table, $expected) {

    $curl = $this->curl($crud, $table);
    $code = $curl['code'];
    //error_log(var_export($curl['headers'], true));
    $this->assertEquals($expected, $code);
  }

  public function provider() {
    return array(
      array('c', 'posts', 204),
      array('r', 'posts', 200),
      array('u', 'posts', 404),
      array('d', 'posts', 404),
      array('c', 'posts2', 404),
      array('r', 'posts2', 404),
      array('u', 'posts2', 404),
      array('d', 'posts2', 404),
    );
  }

  private function curl($crud, $table, $id='', $data=array()) {
    $id = (empty($id)) ? '' : "/$id";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8083/$table$id");
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