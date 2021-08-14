<?php
namespace QuickDRY\Connectors;

/**
 * Class MSSQL_Base
 */
class MSSQL_A extends MSSQL_Core
{
  protected static ?MSSQL_Connection $connection = null;

  protected static function _connect()
  {
    if (!defined('MSSQL_HOST')) {
      exit('MSSQL_HOST');
    }
    if (!defined('MSSQL_USER')) {
      exit('MSSQL_USER');
    }
    if (!defined('MSSQL_PASS')) {
      exit('MSSQL_PASS');
    }
    if (is_null(static::$connection)) {
      static::$DB_HOST = MSSQL_HOST;
      static::$connection = new MSSQL_Connection(MSSQL_HOST, MSSQL_USER, MSSQL_PASS);
    }
  }

  /**
   * @param bool $val
   */
  public static function SetIgnoreDuplicateError(bool $val)
  {
    self::_connect();
    self::$connection->IgnoreDuplicateError = $val;
  }

  /**
   * @return string|null
   */
  public static function _Table(): ?string
  {
    return static::$table;
  }
}