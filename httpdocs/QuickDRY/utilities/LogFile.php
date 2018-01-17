<?php
class LogFile
{
    public function __construct()
    {
        if(!is_dir(DOC_ROOT_PATH . '/logs')) {
            mkdir(DOC_ROOT_PATH . '/logs');
        }
    }

    public function Insert($filename, $message, $echo = false)
    {
        $f = preg_replace('/[^a-z]/si','_', $filename) . '.' . Dates::Datestamp();
        $log_path = DOC_ROOT_PATH . '/logs/' . $f . '.log';

        $fp = fopen($log_path,'a');

        if(false === $fp) {
            error_get_last();
            error_log('Unable to log to ' . DOC_ROOT_PATH . '/logs/' . $f . '.log. Please check permissions');
            return;
        }

        $msg = [];
        $msg []= GUID;
        $msg []= time();
        $msg []= Dates::Timestamp();
        $msg [] = getcwd() . '/' . $filename;
        $msg [] = Network::Interfaces();
		$msg [] = is_array($message) ? json_encode($message) : $message;
        $msg = implode("\t", $msg);
        fwrite($fp, $msg . PHP_EOL);
        fclose($fp);

        if($echo) {
            $msg = [];
            $msg []= GUID;
            $msg [] = getcwd() . '/' . $filename;
            $msg [] = Network::Interfaces();
            $msg [] = is_array($message) ? json_encode($message) : $message;
            $msg = implode("\t", $msg);
            echo Dates::TimeString($msg);
        }
    }
}