SleepyMySQL
============

A live RESTful API for your MySQL database with permissions.

This is _not_ an API generator. This is for quickly creating a database and throwing REST in front of it.

composer.json
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/AnimasWeb/sleepy-mysql.git"
    }
  ],
  "require": {
    "AnimasWeb/sleepy-mysql": "dev-master"
  }
}
```

AllowAll
---------

```php
$db_config = array(
  'server' => 'localhost',
  'database' => 'test',
  'username' => 'root',
  'password' => '',
  'verbose' => false
);

$po = new \SleepyMySQL\SMPermissionsAllowAll();
$arrest = new \SleepyMySQL\SleepyMySQL($db_config, $po);
$arrest->rest();
```