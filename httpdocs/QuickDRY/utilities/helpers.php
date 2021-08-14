<?php

use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\Debug;
use QuickDRY\Utilities\Navigation;

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
    if ($null_string && is_null($v)) {
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
 * @param $filename
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
function BufferInclude($file): string
{
  ob_start();
  include $file;
  $_PAGE_HTML = ob_get_contents();
  ob_end_clean();
  return $_PAGE_HTML;
}


/**
 * @param int $count
 * @param string|null $params
 * @param string|null $_SORT_BY
 * @param string|null $_SORT_DIR
 * @param int|null $_PER_PAGE
 * @param string|null $_URL
 * @param bool $ShowViewAll
 * @return string
 */
function BootstrapPaginationLinks(
  int $count,
  string $params = null,
  string $_SORT_BY = null,
  string $_SORT_DIR = null,
  int $_PER_PAGE = null,
  string $_URL = null,
  bool $ShowViewAll = true): string
{
  return Navigation::BootstrapPaginationLinks($count, $params, $_SORT_BY, $_SORT_DIR, $_PER_PAGE, $_URL, $ShowViewAll);
}

// debug functions

function CleanHalt($var, $message = null)
{
  Debug::CleanHalt($var, $message);
}

/**
 * @param $var
 * @param null $message
 */
function Halt($var, $message = null)
{
  Debug::Halt($var, $message);
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