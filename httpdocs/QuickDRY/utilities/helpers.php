<?php

use QuickDRY\Utilities\Dates;

function IsCLI(): bool
{
    return (php_sapi_name() === 'cli');
}

/**
 * @param $arr
 * @param bool $null_string
 * @param null $prop_definitions
 * @return mixed
 */
function ToArray($arr, bool $null_string = false, $prop_definitions = null)
{
    // Cleans up an array of values so that it can ben
    // put into a database object and be saved into the database
    foreach ($arr as $k => $v) {
        if (is_object($v) && get_class($v) === 'DateTime') {
            $arr[$k] = isset($prop_definitions[$k]['type']) && strcasecmp($prop_definitions[$k]['type'], 'date') == 0 ? Dates::Datestamp($v) : Dates::Timestamp($v);
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
function EchoMemoryUsage(): string
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
function IsWindows(): bool
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}


/**
 * @return string
 */
function GUID(): string
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

/**
 * @return string
 */
function RecID(): string
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
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
 * @param $url
 * @return bool|string
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



function GetFolderFilesRecursive($path): array
{
    $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::FOLLOW_SYMLINKS);
    $filter = new MyRecursiveFilterIterator($directory);
    $iterator = new RecursiveIteratorIterator($filter);
    $files = [];
    foreach ($iterator as $info) {
        $files[] = $info->getPathname();
    }
    return $files;
}

class MyRecursiveFilterIterator extends RecursiveFilterIterator
{
    public function accept(): bool
    {
        $filename = $this->current()->getFilename();
        // Skip hidden files and directories.
        if ($filename[0] === '.') {
            return false;
        }
        return true;
    }
}