<?php
class Debug extends SafeClass
{
    public static function debug_string_backtrace() {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

        // Renumber backtrace items.
        //$trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

        return $trace;
    }

    public static function CleanHalt($var)
    {
        self::Show($var, true, false);
    }

    public static function Halt($var)
    {
        self::Show($var, true, true);
    }

    public static function Show($var, $exit = true, $show_backtrace = true)
    {
        echo '<pre>';
        if(is_object($var) || is_array($var))
            $t = print_r($var, true);
        else
            $t = $var;
        $t = str_replace('<','&lt;',$t);
        echo $t;
        echo '</pre>';
        if($exit)
        {
            if($show_backtrace)
                echo '<pre>' . self::debug_string_backtrace() . '</pre>';
            exit();
        }
    }
}


