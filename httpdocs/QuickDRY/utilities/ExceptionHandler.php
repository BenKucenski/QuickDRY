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
            Halt($err);
        }
        self::LogError(-1, $err, '', '');
    }

    public static function LogError($errno, $errstr, $errfile, $errline)
    {
        Log::Insert([$errno, $errstr, $errfile, $errline]);
    }

    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return bool
     * @throws Exception
     */
    public static function Error($errno, $errstr, $errfile, $errline)
    {
        if (defined('SHOW_ERRORS') && SHOW_ERRORS) {
            if ($errno != 8 || (defined('SHOW_NOTICES') && SHOW_NOTICES)) { // don't show notice errors on the page unless explicitly told to
                self::LogError($errno, $errstr, $errfile, $errline);
                throw new Exception(json_encode([$errno, $errstr, $errfile, $errline]));
            }
        }
        self::LogError($errno, $errstr, $errfile, $errline);
        return false;
    }

    /**
     *
     */
    public static function Fatal()
    {
        $error = error_get_last();
        if ($error['type'] == E_ERROR) {
            try {
                self::Error($error['type'], $error['message'], $error['file'], $error['line']);
            } catch(Exception $e) {
                Halt($e);
            }
        }
    }

    public static function Init()
    {
        register_shutdown_function(['ExceptionHandler', 'Fatal']);

        set_exception_handler(['ExceptionHandler', 'Exception']);
        set_error_handler(['ExceptionHandler', 'Error']);

    }
}