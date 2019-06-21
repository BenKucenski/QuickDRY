<?php

/**
 * @param $arr
 * @param bool $null_blank
 * @return mixed
 */
function ToArray($arr, $null_string = false, $prop_definitions = null)
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
function BootstrapPaginationLinks($count, $params = null, $_SORT_BY = null, $_SORT_DIR = null, $_PER_PAGE = null, $_URL = null, $ShowViewAll = true)
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
 */
function Halt($var, $message = null)
{
    Debug::Halt($var, $message);
}

// Strings functions

/**
 * @param $value
 * @return mixed
 */
function FormFilter($value)
{
    return Strings::FormFilter($value);
}

/**
 * @param $var
 * @return string
 */
function echo_js($var)
{
    return Strings::EchoJS($var);
}

/**
 * @param $data
 * @return string
 */
function array_to_xml($data)
{
    return Strings::ArrayToXML($data);
}

/**
 * @param $value
 * @param int $brightness
 * @param int $max
 * @param int $min
 * @param string $thirdColorHex
 * @return string
 */
function percent2Color($value, $brightness = 255, $max = 100, $min = 0, $thirdColorHex = '00')
{
    return Strings::PercentToColor($value, $brightness, $max, $min, $thirdColorHex);
}

/**
 * @param $string
 * @return string
 */
function xml_entities($string)
{
    return Strings::XMLEntities($string);
}


/**
 * @param $num
 *
 * @return string
 */
function BigInt($num)
{
    return Strings::BigInt($num);
}

/**
 * @param      $val
 * @param bool $dollar_sign
 *
 * @return mixed|string
 */
function Currency($val, $dollar_sign = true, $sig_figs = 2)
{
    return Strings::Currency($val, $dollar_sign, $sig_figs);
}

/**
 * @param $str
 *
 * @return mixed
 */
function escapeXml($str)
{
    return Strings::EscapeXml($str);
}

/**
 * @param $desc
 *
 * @return string
 */
function makeUTF($desc)
{
    return Strings::MakeUTF($desc);
}

/**
 * @param $url
 * @param $key
 *
 * @return mixed
 */
function remove_string_var($url, $key)
{
    return Strings::RemoveStringVar($url, $key);
}

/**
 * @param $arg
 * @param $replaceWith
 *
 * @return mixed
 */
function replaceSpecialChar($arg, $replaceWith)
{
    return Strings::ReplaceSpecialChar($arg, $replaceWith);
}

/**
 * @param $val
 * @return float|string
 */
function Numeric($val)
{
    return Strings::Numeric($val);
}

/**
 * @param $val
 * @return mixed
 */
function NumericPhone($val)
{
    return NumericPhone($val);
}

/**
 * @param $val
 * @return string
 */
function phone_number($val)
{
    return Strings::PhoneNumber($val);
}

/**
 * @param $count
 * @return string
 */
function GetPlaceholders($count)
{
    return Strings::GetPlaceholders($count);
}

/**
 * @param $val
 * @return int
 */
function WordCount($val)
{
    return Strings::WordCount($val);
}

/**
 * @param $hex
 * @return string
 */
function Base16to10($hex)
{
    return Strings::Base16to10($hex);
}

/**
 * @param $md5
 * @return string
 */
function MD5toBase62($md5)
{
    return Strings::MD5toBase62($md5);
}

/**
 * @param $str
 * @param $length
 * @param bool $words
 * @return string
 */
function Truncate($str, $length, $words = false)
{
    return Strings::Truncate($str, $length, $words);
}

/**
 * @param $json
 * @return array|string
 */
function fix_json($json)
{
    return Strings::FixJSON($json);
}


/**
 * @param $txt
 *
 * @return mixed
 */
function InlineSafe($txt)
{
    return Strings::InlineSafe($txt);
}

/**
 * @param     $string
 * @param int $length
 *
 * @return string
 */
function TrimString($string, $length = 150)
{
    return Strings::TrimString($string, $length);
}


/**
 * @param $text
 *
 * @return mixed
 */
function ToSearchable($text)
{
    return Strings::ToSearchable($text);
}

/**
 * @param $num
 *
 * @return float|int
 */
function NumberOfZeros($num)
{
    return Strings::NumberOfZeros($num);

}


/**
 * @param $number
 *
 * @return string
 */
function PhoneNumber($number)
{
    return Strings::PhoneNumber2($number);
}


/**
 * @param $text
 *
 * @return string
 */
function StringToHTML($text)
{
    return Strings::StringToHTML($text);
}

/**
 * @param $text
 *
 * @return string
 */
function StringToBR($text)
{
    return Strings::StringToBR($text);
}


/**
 * @param        $num
 * @param int $dec
 * @param string $null
 *
 * @return string
 */
function smart_number_format($num, $dec = 2, $null = '-')
{
    return Strings::SmartNumberFormat($num, $dec, $null);
}

/**
 * @param     $num
 * @param int $dec
 *
 * @return string
 */
function form_number_format($num, $dec = 2, $comma = '')
{
    return Strings::FormNumberFormat($num, $dec, $comma);
}

/**
 * @param $pattern
 * @param $multiplier
 * @return string
 */
function StringRepeatCS($pattern, $multiplier)
{
    return Strings::StringRepeatCS($pattern, $multiplier);
}

/**
 * @param $array
 * @param string $accessor
 * @param string $function
 * @return string
 */
function createQuickList($array, $accessor = '$item', $function = 'Show')
{
    return Strings::CreateQuickList($array, $accessor, $function);
}

/**
 * @param $var
 * @param string $default
 * @return string
 */
function ShowOrDefault($var, $default = 'n/a')
{
    return Strings::ShowOrDefault($var, $default);
}


// HTTP

/**
 * @param $json
 * @param int $HTTP_STATUS
 */
function exit_json($json, $HTTP_STATUS = HTTP_STATUS_OK)
{
    return HTTP::ExitJSON($json, $HTTP_STATUS);
}

/**
 * @param $serialized
 * @return array
 */
function PostFromSerialized($serialized)
{
    return HTTP::PostFromSerialized($serialized);
}

