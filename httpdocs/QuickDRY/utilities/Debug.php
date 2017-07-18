<?php
function CleanHalt($var)
{
    Debug($var, null, true, true, false);
}

/**
 * @param $var
 */
function Halt($var, $message = null)
{
    Debug($var, isset($message) ? $message : null, true, true);
}

/**
 * @param      $var
 * @param null $msg
 * @param bool $print
 * @param bool $exit
 * @param bool $backtrace
 */
function Debug($var, $msg = null, $print = false, $exit = false, $backtrace = true)
{
    $finalMsg = '';
    if($msg)
        $finalMsg .= '<h3>' . $msg . '</h3>';
    $finalMsg .=  '<pre>';
    $finalMsg .= print_r($var, true);
    $finalMsg .=  "\r\n\r\n";
    if($backtrace)
        $finalMsg .=  debug_string_backtrace();
    $finalMsg .=  '</pre>' .PHP_EOL;

    if(defined('IS_PRODUCTION') && IS_PRODUCTION) {
        $t = Mailer::Queue(SMTP_DEBUG_EMAIL, SMTP_FROM_NAME, 'HALT: ' . $_SERVER['REQUEST_URI'], $finalMsg);
        $t->Send();
        exit('An Error Occured.  Please Try Again Later.');
    }
    if ($print !== false) {
        echo $finalMsg;
    }

    if($exit) {
        exit();
    }
}

/**
 * @param      $var
 * @param bool $exit
 * @param bool $backtrace
 * @param bool $display
 */
function DebugCL($var, $exit = false, $backtrace = true, $display = true)
{
    $res = "\n----\n";
    if(is_object($var) || is_array($var))
        $t = print_r($var, true);
    else
        $t = $var;
    $res .= $t;
    $res .= "\n----\n";
    if($backtrace)
        $res .= "\n----\n" . debug_string_backtrace() . "\n----\n";

    if($display)
        echo $res;

    if($exit)
        exit();

    if(!$display)
        trigger_error($res);
}

/**
 * @return mixed|string
 */
function debug_string_backtrace() {
    ob_start();
    debug_print_backtrace();
    $trace = ob_get_contents();
    ob_end_clean();

    // Remove first item from backtrace as it's this function which
    // is redundant.
    $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);


    return $trace;
}

function convert_error_no($errno)
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

