<?php
/**
 * Sleepy MySQL
 * A "plug-n-play" RESTful API for your MySQL database with permissions.
 *
 * <code>
 * $po = new \SleepyMySQL\SMPermissionsAllowAll();
 * $sm = new \SleepyMySQL\SleepyMySQL($db_config, $po);
 * $sm->rest();
 * </code>
 *
 * Modified: Leo Lutz
 * Author: Gilbert Pellegrom
 * Website: http://dev7studios.com
 * Date: Jan 2013
 * Version 1.0
 */

namespace SleepyMySQL;

if( !class_exists('SMDatabase') ) {
  require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SMDatabase.php');
}

class SleepyMySQL {

  /**
   * The instance of Database
   *
   * @var SMDatabase
   */
  private $db;
  /**
   * The structure of the database
   *
   * @var array
   */
  private $db_structure;
  /**
   * The URI segments
   *
   * @var array
   */
  private $segments;
  /**
   * Array of custom table indexes
   *
   * @var array
   */
  private $table_index;
  /**
   * Permissions object
   *
   * @var \SleepyMySQL\SMPermissions
   */
  private $permissions;

  private $memcached;


  /**
   * Create an instance, optionally setting a base URI
   *
   * @param array $db_config An array of database config options. Format:
   * <code>
   * $db_config = array(
   *    'server'   => 'localhost',
   *    'database' => '',
   *    'username' => '',
   *    'password' => '',
   *    'verbose' => false
   * );
   *</code>
   * @param $permissions \SleepyMySQL\SMPermissions The object to check for permission
   * @access public
   * @throws \Exception If database init fails
   */
  public function __construct($db_config, $permissions) {
    if(class_exists('Memcached',false)) {
       $this->memcached = new \Memcached();
       $this->memcached->addServer('localhost', 11211);
    }

    $this->db = new SMDatabase($db_config);
    if( !$this->db->init() ) throw new \Exception($this->db->get_error());

    $this->db_structure = $this->map_db($db_config['database']);
    $this->segments = $this->get_uri_segments();
    $this->table_index = array();
    $this->permissions = $permissions;
  }

  /**
   * Handle the REST calls and map them to corresponding CRUD
   *
   * @access public
   */
  public function rest() {
    header('Content-type: application/json');

    $table = $this->segment(0);

    /*
    create > POST   /table
    read   > GET    /table[/id]
    update > PUT    /table/id
    delete > DELETE /table/id
    */
    switch ($_SERVER['REQUEST_METHOD']) {
      case 'POST':
        if(!$this->permissions->hasPermission($table, SMPermissions::TABLE_WRITE)) $this->output_403();
        $this->create();
        break;
      case 'GET':
        if(!$this->permissions->hasPermission($table, SMPermissions::TABLE_READ)) $this->output_403();
        $this->read();
        break;
      case 'PUT':
        if(!$this->permissions->hasPermission($table, SMPermissions::TABLE_WRITE)) $this->output_403();
        $this->update();
        break;
      case 'DELETE':
        if(!$this->permissions->hasPermission($table, SMPermissions::TABLE_WRITE)) $this->output_403();
        $this->delete();
        break;
    }
  }

  /**
   * Add a custom index (usually primary key) for a table
   *
   * @param string $table Name of the table
   * @param string $field Name of the index field
   * @access public
   */
  public function set_table_index($table, $field) {
    $this->table_index[$table] = $field;
  }

  /**
   * Get the table name
   *
   * @return string Table name
   * @access public
   */
  public function get_table_name() {
    return $this->segment(0);
  }


  /**
   * Get the db connection
   *
   * @return SMDatabase The database connection
   * @access public
   */
  public function get_db() {
    return $this->db;
  }


  /**
   * Map the stucture of the MySQL db to an array
   *
   * @param string $database Name of the database
   * @return array Returns array of db structure
   * @access private
   */
  private function map_db($database) {
    $cached = $this->fetch_cached_map();
    if($cached) {
      return $cached;
    }
    // Map db structure to array
    $tables_arr = array();
    $this->db->query('SHOW TABLES FROM ' . $database);
    while( $table = $this->db->fetch_array() ) {
      if( isset($table['Tables_in_' . $database]) ) {
        $table_name = $table['Tables_in_' . $database];
        $tables_arr[$table_name] = array();
      }
    }
    foreach( $tables_arr as $table_name => $val ) {
      $this->db->query('SHOW COLUMNS FROM ' . $table_name);
      $fields = $this->db->fetch_all();
      $tables_arr[$table_name] = $fields;
    }
    return $tables_arr;
  }

  private function fetch_cached_map() {
    if (!$this->memcached) return null;
    $serialized = $this->memcached->get('table-map');
    if($serialized) {
      error_log('loaded cached map');
      return unserialize($serialized);
    }
    return null;
  }

  private function set_cached_map($table_map) {
    if (!$this->memcached) return null;
    $this->memcached->set('table-map', serialize($table_map), 3600);
    error_log('cached map');
  }

  /**
   * Get the URI segments from the URL
   *
   * @return array Returns array of URI segments
   * @access private
   */
  private function get_uri_segments() {
    // Fix REQUEST_URI if required
    if( !isset($_SERVER['REQUEST_URI']) ) {
      $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
      if( isset($_SERVER['QUERY_STRING']) ) $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }

    // Server params
    $scriptName = $_SERVER['SCRIPT_NAME']; // <-- "/foo/index.php"
    $requestUri = $_SERVER['REQUEST_URI']; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
    $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''; // <-- "test=abc" or ""

    // Physical path
    if (strpos($requestUri, $scriptName) !== false) {
      $physicalPath = $scriptName; // <-- Without rewriting
    } else {
      $physicalPath = str_replace('\\', '', dirname($scriptName)); // <-- With rewriting
    }

    // Virtual path
    $url = substr_replace($requestUri, '', 0, strlen($physicalPath)); // <-- Remove physical path
    $url = str_replace('?' . $queryString, '', $url); // <-- Remove query string
    $url = ltrim($url, '/'); // <-- Ensure no leading slash

    return explode('/', $url);
  }

  /**
   * Get a URI segment
   *
   * @param int $index Index of the URI segment
   * @return mixed Returns URI segment or false if none exists
   * @access private
   */
  private function segment($index) {
    if( isset($this->segments[$index]) ) return $this->segments[$index];
    return false;
  }

  /**
   * Handles a POST and inserts into the database
   *
   * @access private
   */
  private function create() {
    $table = $this->segment(0);

    if( !$table || !isset($this->db_structure[$table]) ) {
      $this->output_404();
    }

    if( $data = $this->_post() ) {
      $this->db->insert($table, $data)
          ->query();
      $this->output_success();
    } else {
      $this->output_204();
    }
  }

  /**
   * Handles a GET and reads from the database
   *
   * @access private
   */
  private function read() {
    $table = $this->segment(0);
    $id = intval($this->segment(1));

    if( !$table || !isset($this->db_structure[$table]) ) {
      $this->output_404();
    }
//error_log($id);
    if( $id && is_int($id) ) {
      $index = 'id';
      if( isset($this->table_index[$table]) ) $index = $this->table_index[$table];
      $this->db->select('*')
          ->from($table)
          ->where($index, $id)
          ->query();
      if( $result = $this->db->fetch_array() ) {
        die(json_encode($result));
      } else {
        $this->output_204();
      }
    } else {
      $this->db->select('*')
          ->from($table)
          ->order_by($this->_get('order_by'), $this->_get('order'))
          ->limit(intval($this->_get('limit')), intval($this->_get('offset')))
          ->query();
      if( $result = $this->db->fetch_all() ) {
        die(json_encode($result));
      } else {
        $this->output_204();
      }
    }
  }

  /**
   * Handles a PUT and updates the database
   *
   * @access private
   */
  private function update() {
    $table = $this->segment(0);
    $id = intval($this->segment(1));
    if( !$table || !isset($this->db_structure[$table]) || !$id ) {
      $this->output_404();
    }

    $index = 'id';
    if( isset($this->table_index[$table]) ) $index = $this->table_index[$table];
    $this->db->select('*')
        ->from($table)
        ->where($index, $id)
        ->query();
    if( $result = $this->db->fetch_array() ) {
      $this->db->update($table)
          ->set($this->_put())
          ->where($index, $id)
          ->query();
      $this->output_success();
    } else {
      $this->output_204();
    }
  }

  /**
   * Handles a DELETE and deletes from the database
   *
   * @access private
   */
  private function delete() {
    $table = $this->segment(0);
    $id = intval($this->segment(1));

    if( !$table || !isset($this->db_structure[$table]) || !$id ) {
      $this->output_404();
    }

    $index = 'id';
    if( isset($this->table_index[$table]) ) $index = $this->table_index[$table];
    $this->db->select('*')
        ->from($table)
        ->where($index, $id)
        ->query();
    if( $result = $this->db->fetch_array() ) {
      $this->db->delete($table)
          ->where($index, $id)
          ->query();
      $this->output_success();
    } else {
      $this->output_204();
    }
  }

  /**
   * Helper function to retrieve $_GET variables
   *
   * @param string $index Optional $_GET index
   * @return mixed Returns the $_GET var at the specified index,
   *               the whole $_GET array or false
   * @access private
   */
  private function _get($index = '') {
    if( $index ) {
      if( isset($_GET[$index]) && $_GET[$index] ) return strip_tags($_GET[$index]);
    } else {
      if( isset($_GET) && !empty($_GET) ) return $_GET;
    }
    return false;
  }

  /**
   * Helper function to retrieve $_POST variables
   *
   * @param string $index Optional $_POST index
   * @return mixed Returns the $_POST var at the specified index,
   *               the whole $_POST array or false
   * @access private
   */
  private function _post($index = '') {
    return $this->_put();
    //        if($index){
    //            if(isset($_POST[$index]) && $_POST[$index]) return $_POST[$index];
    //        } else {
    //            if(isset($_POST) && !empty($_POST)) return $_POST;
    //        }
    //        return false;
  }

  /**
   * Helper function to retrieve PUT variables
   *
   * @return mixed Returns the contents of PUT as an array
   * @access private
   */
  private function _put() {
    $output = json_decode(file_get_contents('php://input'), true);
//    error_log(var_export($output,true));
    return $output;
  }

  public function get_put() {
    return $this->_put();
  }

  public function output_success() {
    $this->output('success', 'Success', 200);
  }

  public function output_204() {
    $this->output('error', 'No Content', 204);
  }

  public function output_403() {
    $this->output('error', 'Forbidden', 403);
  }

  public function output_404() {
    $this->output('error', 'Not Found', 404);
  }

  public function output($type, $message, $code) {
    header("HTTP/1.0 $code $message");
    $obj = array($type => array(
      'message' => $message,
      'code' => $code
    ));

    die(json_encode($obj));
  }
}