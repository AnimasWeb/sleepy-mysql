<?php

namespace SleepyMySQL;

abstract class SMPermissions {
  const TABLE_READ = 'r';
  const TABLE_WRITE = 'w';
  abstract public function hasPermission($table, $function);
}