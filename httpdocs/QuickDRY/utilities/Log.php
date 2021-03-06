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
    private static $StartTime;

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

    public static function Print($message)
    {
        self::Insert($message, true, false);
    }

    public static function File($message)
    {
        self::Insert($message, false, true);
    }

    public static function Elastic($message)
    {
        if (!isset(self::$StartTime[GUID])) {
            self::$StartTime[GUID] = time();
        }

        $dir = getcwd();
        $dir = str_replace('\\', '/', $dir);

        $script = $_SERVER["SCRIPT_FILENAME"];
        $script = str_replace('.\\', '', $script);
        $dirs = explode('/', $dir);

        $msg = [];
        $msg ['GUID'] = GUID;
        $msg ['TIMESTAMP'] = Dates::Timestamp();
        $msg ['MINUTES'] = sprintf('%08.2f', (time() - self::$StartTime[GUID]) / 60);
        $msg ['FULLPATH'] = $dir . '/' . $script;
        $msg ['SHORTPATH'] = $dirs[sizeof($dirs) - 1];
        $msg ['SCRIPT'] = $script;
        $msg ['NETWORK'] = Network::Interfaces();
        $msg ['MESSAGE'] = is_array($message) || is_object($message) ? json_encode($message) : $message;

        // Insert expects an array of hash tables
        $msg = [$msg];
        $res = Elastic_A::Insert('cron', 'log', $msg);
        if($res['error']) {
            CleanHalt($res);
        }
    }
}