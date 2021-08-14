<?php

use QuickDRY\Utilities\Debug;

/**
 * Class ACCESS_B
 */
class ACCESS_B extends ACCESS_Core
{
  protected static ?ACCESS_Connection $connection = null;

  protected static function _connect()
  {
    if (is_null(static::$connection)) {
      if (!defined('ACCESSB_FILE')) {
        Debug::Halt('ACCESS_FILE not defined');
        exit;
      }
      static::$DB_HOST = ACCESSB_FILE;
      static::$connection = new ACCESS_Connection(ACCESSB_FILE, defined('ACCESSB_USER') ? ACCESSB_USER : null, defined('ACCESSB_PASS') ? ACCESSB_PASS : null);
    }
  }
}