<?php

/**
 * Class ExceptionHandler
 */
class ExceptionHandler
{
    /**
     * @param $err
     */
    public static function Exception($err)
    {
        if (defined('SHOW_ERRORS') && SHOW_ERRORS) {
            Log::Insert($err, true);
        }
        LogError(-1, $err, '', '');
    }


    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     *
     * @return bool
     */
    public static function Error($errno, $errstr, $errfile, $errline)
    {
        if (defined('SHOW_ERRORS') && SHOW_ERRORS) {
            if ($errno != 8 || (defined('SHOW_NOTICES') && SHOW_NOTICES)) { // don't show notice errors on the page unless explicitly told to
                Debug::Halt([$errno, $errstr, $errfile, $errline]);
            }
        }
        //LogError($errno, $errstr, $errfile, $errline);
        return false;
    }

    /**
     *
     */
    public static function Fatal()
    {
        $error = error_get_last();
        if ($error['type'] == E_ERROR)
            self::Error($error['type'], $error['message'], $error['file'], $error['line']);
    }

    public static function Init()
    {
        register_shutdown_function(['ExceptionHandler', 'Fatal']);

        set_exception_handler(['ExceptionHandler', 'Exception']);
        set_error_handler(['ExceptionHandler', 'Error']);

    }
}