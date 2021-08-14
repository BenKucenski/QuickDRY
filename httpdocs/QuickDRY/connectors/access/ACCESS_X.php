<?php
namespace QuickDRY\Connectors;

/**
 * Class ACCESS_X
 */
class ACCESS_X extends ACCESS_Core
{
  protected static ?ACCESS_Connection $connection = null;
  public static string $ACCESS_FILE;
  public static string $ACCESS_USER;
  public static string $ACCESS_PASS;

  protected static function _connect()
  {
    if (is_null(static::$connection)) {
      static::$DB_HOST = self::$ACCESS_FILE;
      static::$connection = new ACCESS_Connection(self::$ACCESS_FILE, self::$ACCESS_USER, self::$ACCESS_PASS);
    }
  }
}