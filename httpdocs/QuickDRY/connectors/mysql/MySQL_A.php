<?php
namespace QuickDRY\Connectors;

/**
 * Class MySQLBase
 */
class MySQL_A extends MySQL_Core
{
  protected static ?MySQL_Connection $connection = null;

  protected static function _connect()
  {
    if (!defined('MYSQLA_HOST')) {
      exit('MYSQLA_HOST');
    }

    if (!defined('MYSQLA_USER')) {
      exit('MYSQLA_USER');
    }

    if (!defined('MYSQLA_PASS')) {
      exit('MYSQLA_PASS');
    }

    if (!defined('MYSQLA_PORT')) {
      exit('MYSQLA_PORT');
    }

    if (is_null(static::$connection)) {
      static::$DB_HOST = MYSQLA_HOST;
      static::$connection = new MySQL_Connection(MYSQLA_HOST, MYSQLA_USER, MYSQLA_PASS, MYSQLA_PORT);
    }
  }
}