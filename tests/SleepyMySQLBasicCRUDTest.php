<?php

if(!class_exists('FixtureTestCase')) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'FixtureTestCase.php');
}

class SleepyMySQLBasicCRUDTest extends FixtureTestCase {

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
   */
  public function testReadIndex() {
    $curl = $this->curl('r', 'posts');
    $code = $curl['code'];
    $this->assertEquals(200, $code);
    $data = json_decode($curl['body'], true);
    $this->assertEquals(2, sizeof($data));
  }

  /**
   * @depends testServerUp
   */
  public function testReadItems() {
    $curl = $this->curl('r', 'posts', 1);
    $code = $curl['code'];
    $this->assertEquals(200, $code);
    $data = json_decode($curl['body'], true);
    $this->assertArrayHasKey('post_name', $data);
    $this->assertEquals('hello-world', $data['post_name']);

    $curl = $this->curl('r', 'posts', 2);
    $code = $curl['code'];
    $this->assertEquals(200, $code);
    $data = json_decode($curl['body'], true);
    $this->assertArrayHasKey('post_name', $data);
    $this->assertEquals('about', $data['post_name']);
  }

  /**
   * @depends testServerUp
   */
  public function testCreate() {
    $new = array(
      'post_content' => 'Test post!',
      'post_title' => 'Testing',
      'post_name' => 'testing',
      'guid' => 'http://wordpress.local/?page_id=2'
    );
    $curl = $this->curl('c', 'posts', '', json_encode($new));
    $code = $curl['code'];
    $this->assertEquals(200, $code);

    $curl = $this->curl('r', 'posts', 3);
    $this->assertEquals(200, $curl['code']);
    $data = json_decode($curl['body'], true);
    $this->assertNotEquals($new, $data);
    $new['id'] = 3;
    $this->assertEquals($new, $data);
  }

  private function curl($crud, $table, $id='', $data='') {
    $id = (empty($id)) ? '' : "/$id/";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8083/$table$id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    switch($crud) {
      case 'c':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        break;
      case 'r':
        break;
      case 'u':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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