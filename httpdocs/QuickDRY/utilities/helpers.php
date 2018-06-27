<?php

/**
 * @param $arr
 * @param bool $null_blank
 * @return mixed
 */
function ToArray($arr, $null_string = false)
{
    // Cleans up an array of values so that it can ben
    // put into a database object and be saved into the database
    foreach ($arr as $k => $v) {
        if (is_object($v) && get_class($v) === 'DateTime') {
            $arr[$k] = Dates::Timestamp($v);
        }
        if($null_string && is_null($v)) {
            $arr[$k] = 'null';
        }
    }
    return $arr;
}

/**
 * @return string
 */
function EchoMemoryUsage()
{
    $mem_usage = memory_get_usage(true);

    if ($mem_usage < 1024) {
        return $mem_usage . " bytes";
    }
    if ($mem_usage < 1048576) {
        return round($mem_usage / 1024, 2) . " kilobytes";
    }

    return round($mem_usage / 1048576, 2) . " megabytes";
}

/**
 * @return bool
 */
function IsWindows()
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}


/**
 * @return string
 */
function GUID()
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

/**
 * @return string
 */
function RecID()
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


/**
 * @param int $time
 *
 * @return bool|string
 */

function LoadFile($filename)
{
    if (!file_exists($filename)) {
        Halt($filename . ' doesn\'t exist');
    }

    if (filesize($filename) == 0) {
        return '';
    }

    $fp = fopen($filename, 'r');
    $f = fread($fp, filesize($filename));
    fclose($fp);
    return $f;
}

/**
 * @param $cmd
 * @return int|string
 */
function run_in_background($cmd)
{
    if (IsWindows()) {
        pclose(popen("start " . $cmd, "r"));
        return 0;
    }

    $PID = exec("nohup $cmd 1>/dev/null & echo $!");
    return ($PID);
}

/**
 * @param $file
 *
 * @return string
 */
function BufferInclude($file)
{
    ob_start();
    include $file;
    $_PAGE_HTML = ob_get_contents();
    ob_end_clean();
    return $_PAGE_HTML;
}

/**
 * @param $url
 * @return mixed
 */
function getTinyUrl($url)
{
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url=' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * @param $count
 * @param string $params
 * @param null $_SORT_BY
 * @param null $_SORT_DIR
 * @param null $_PER_PAGE
 * @param null $_URL
 * @return string
 */
function BootstrapPaginationLinks($count, $params = null, $_SORT_BY = null, $_SORT_DIR = null, $_PER_PAGE = null, $_URL = null)
{
    return Navigation::BootstrapPaginationLinks($count, $params, $_SORT_BY, $_SORT_DIR, $_PER_PAGE, $_URL);
}
