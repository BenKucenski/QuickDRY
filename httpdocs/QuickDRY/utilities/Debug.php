<?php

class Debug extends SafeClass
{
    public static function CleanHalt($var, $message = null)
    {
        static::_Debug($var, $message, true, true, false);

    }

    public static function Halt($var, $message = null)
    {
        static::_Debug($var, $message, true, true, true);
    }

    public static function CleanHaltCL($var, $message = null)
    {
        static::_DebugCL($var, $message, true, true, false);

    }

    public static function HaltCL($var, $message = null)
    {
        static::_DebugCL($var, $message, true, true, true);
    }

    public static function _Debug($var, $msg = null, $print = false, $exit = false, $backtrace = true)
    {
        $finalMsg = '';
        if ($msg) {
            $finalMsg .= '<h3>' . $msg . '</h3>';
        }
        $finalMsg .= '<pre>';
        $finalMsg .= print_r($var, true);
        $finalMsg .= "\r\n\r\n";
        if ($backtrace)
            $finalMsg .= static::_debug_string_backtrace();
        $finalMsg .= '</pre>' . PHP_EOL;

        if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_FILENAME'];
            $t = Mailer::Queue(SMTP_DEBUG_EMAIL, SMTP_FROM_NAME, 'HALT: ' . $uri, $finalMsg);
            try {
                $t->Send();
            } catch(Exception $ex) {
                echo $ex->getMessage();
            }
            exit('An Error Occured.  Please Try Again Later.');
        }
        if ($print !== false) {
            echo $finalMsg;
        }

        if ($exit) {
            exit();
        }
    }

    /**
     * @param      $var
     * @param bool $exit
     * @param bool $backtrace
     * @param bool $display
     */
    private static function _DebugCL($var, $msg = null, $print = false, $exit = false, $backtrace = true)
    {
        $res = "\n----\n";
        if ($msg) {
            echo $msg . PHP_EOL;
        }
        if (is_object($var) || is_array($var)) {
            $t = print_r($var, true);
        } else {
            $t = $var;
        }
        $res .= $t;
        $res .= "\n----\n";
        if ($backtrace)
            $res .= "\n----\n" . static::_debug_string_backtrace() . "\n----\n";

        if ($print) {
            echo $res;
        }

        if ($exit)
            exit();

        if (!$print) {
            trigger_error($res);
        }
    }

    /**
     * @return mixed|string
     */
    private static function _debug_string_backtrace()
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        $trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);


        return $trace;
    }

    private static function _convert_error_no($errno)
    {
        switch ($errno) {
            case E_USER_ERROR:
                return 'user error';

            case E_USER_WARNING:
                return 'user warning';

            case E_USER_NOTICE:
                return 'user notice';

            case E_STRICT:
                return 'strict';

            default:
                return 'unknown';
        }
    }
}

function CleanHalt($var, $message = null)
{
    Debug::CleanHalt($var, $message);
}

/**
 * @param $var
 */
function Halt($var, $message = null)
{
    Debug::Halt($var, $message);
}







