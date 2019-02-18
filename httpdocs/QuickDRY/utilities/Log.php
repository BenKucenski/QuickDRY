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

    /**
     *
     */
    private static function _init()
    {
        if (is_null(self::$_log_file)) {
            self::$_log_file = new LogFile();
        }
    }

    /**
     * @param $message
     * @param bool $echo
     */
    public static function Insert($message, $echo = false, $write_to_file = true)
    {
        self::_init();
        if (!defined('GUID')) {
            return;
        }

        self::$_log_file->Insert($_SERVER["SCRIPT_FILENAME"], $message, $echo, $write_to_file);
    }
}