<?php

function ToArray($arr)
{
    foreach ($arr as $k => $v) {
        if (is_object($v) && get_class($v) === 'DateTime') {
            $arr[$k] = Dates::Timestamp($v);
        }
    }
    return $arr;
}

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

function EmailDevelopers($product, $section, $summary, $message)
{

}


function LogQuery($sql, $err, $time)
{

}

function LogError($errno, $errstr, $errfile, $errline)
{

}


function IsWindows()
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}


function mysql_password_hash($input, $hex = true)
{
    $sha1_stage1 = sha1($input, true);
    $output = sha1($sha1_stage1, !$hex);
    return '*' . strtoupper($output);
} //END function mysql_password_hash


function GUID()
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

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
 * @param $col_type
 *
 * @return string
 */
function ColumnTypeToProperty($col_type)
{
    switch (strtolower($col_type)) {
        case 'varchar':
        case 'char':
        case 'keyword':
        case 'text':
            return 'string';

        case 'tinyint unsigned':
        case 'bigint unsigned':
        case 'int unsigned':
            return 'uint';

        case 'numeric':
        case 'tinyint':
        case 'smallint':
        case 'bit':
            return 'int';

        case 'money':
        case 'decimal':
            return 'float';

        case 'smalldatetime':
            return 'datetime';
    }
    return $col_type;
}

/**
 * @param $field
 *
 * @return string
 */
function FieldToDisplay($field)
{
    $t = ucwords(implode(' ', explode('_', $field)));
    $t = str_replace(' ', '', $t);
    if (strcasecmp(substr($t, -2), 'id') == 0)
        $t = substr($t, 0, strlen($t) - 2);
    return CapsToSpaces($t);
}

/**
 * @param $str
 *
 * @return string
 */
function CapsToSpaces($str)
{
    $results = [];
    preg_match_all('/[A-Z\d][^A-Z\d]*/', $str, $results);
    return implode(' ', $results[0]);
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

function isSecure()
{
    if (!isset($_SERVER['HTTPS'])) {
        return false;
    }

    return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
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
    if ($params == null) {
        $params = [];
        foreach ($_GET as $k => $v) {
            if (!in_array($k, ['sort_by', 'dir', 'page', 'per_page'])) {
                $params[] = $k . '=' . $v;
            }
        }
    }
    if (is_array($params)) {
        $params = implode('&', $params);
    }


    $_SORT_BY = $_SORT_BY ? $_SORT_BY : SORT_BY;
    $_SORT_DIR = $_SORT_DIR ? $_SORT_DIR : SORT_DIR;
    $_PER_PAGE = $_PER_PAGE ? $_PER_PAGE : PER_PAGE;
    $_URL = $_URL ? $_URL : CURRENT_PAGE;

    if ($_PER_PAGE > 0) {
        $num_pages = ceil($count / $_PER_PAGE);
        if ($num_pages <= 1) return '';

        $start_page = PAGE - 10;
        $end_page = PAGE + 10;
        if ($start_page < 0)
            $start_page = 0;
        if ($start_page >= $num_pages)
            $start_page = $num_pages - 1;
        if ($end_page < 0)
            $end_page = 0;
        if ($end_page >= $num_pages)
            $end_page = $num_pages - 1;

        $html = '<ul class="pagination">';
        if (PAGE > 10) {
            $html .= '<li class="first"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (0) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&lt;&lt;</a></li>';
            $html .= '<li class="previous"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE - 10) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&lt;</a></li>';
        }

        for ($j = $start_page; $j <= $end_page; $j++) {
            if ($j != PAGE)
                $html .= '<li class="page_number"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j . '&per_page=' . $_PER_PAGE . '&' . $params . '">' . ($j + 1) . '</a></li>';
            else
                $html .= '<li class="page_number"><a href="#">' . ($j + 1) . '</a></li>';
        }
        if (PAGE < $num_pages - 10 && $num_pages > 10) {
            $html .= '<li class="next"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE + 10) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&gt;</a></li>';
            $html .= '<li class="last"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . ($num_pages - 1) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&gt;&gt;</a></li>';
        }

        $html .= '<li class="view_all"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j . '&per_page=0&' . $params . '">View All</a></li>';

        return $html . '</ul>';
    }
    $html = '<br/><ul class="pagination">';
    $html .= '<li class="view_all"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=0&' . $params . '">View Paginated</a></li>';
    return $html . '</ul>';
}
