<?php


/**
 * Class Log
 */
class Log extends SafeClass
{
    /**
     * @var $_log_file LogFile
     */
    private static $_log_file;

    private static function _init()
    {
        if (is_null(self::$_log_file)) {
            self::$_log_file = new LogFile();
        }
    }

    public static function Insert($message, $echo = false)
    {
        self::_init();
        if (!defined('GUID')) {
            return;
        }

        self::$_log_file->Insert($_SERVER["SCRIPT_FILENAME"], $message, $echo);
    }
}