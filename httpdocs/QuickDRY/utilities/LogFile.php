<?php

/**
 * Class LogFile
 */
class LogFile
{
    private static $StartTime;

    public function __construct()
    {
        if(!is_dir(DOC_ROOT_PATH . '/logs')) {
            mkdir(DOC_ROOT_PATH . '/logs');
        }
    }

    /**
     * @param $filename
     * @param $message
     * @param bool $echo
     */
    public function Insert($filename, $message, $echo = false)
    {
        $f = preg_replace('/[^a-z0-9]/si','_', $filename) . '.' . Dates::Datestamp();
        $log_path = DOC_ROOT_PATH . '/logs/' . $f . '.log';

        $fp = fopen($log_path,'a');

        if(false === $fp) {
            error_get_last();
            error_log('Unable to log to ' . DOC_ROOT_PATH . '/logs/' . $f . '.log. Please check permissions');
            return;
        }

        if(is_object($message)) {
            if(method_exists($message,'GetMessage')) {
                $message = $message->GetMessage();
            }
        }
        if(!isset(self::$StartTime[GUID])) {
            self::$StartTime[GUID] = time();
        }

        $msg = [];
        $msg []= GUID;
        $msg []= sprintf('%08.2f', (time() - self::$StartTime[GUID]) / 60);
        $msg []= Dates::Timestamp();
        $msg [] = getcwd() . '/' . $filename;
        $msg [] = Network::Interfaces();
        $msg [] = is_array($message) || is_object($message) ? json_encode($message) : $message;
        $msg = implode("\t", $msg);
        fwrite($fp, $msg . PHP_EOL);
        fclose($fp);

        if($echo) {
            echo $msg . PHP_EOL;
        }
    }
}