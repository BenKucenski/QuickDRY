<?php

function ReloadPage()
{
    header('location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

function FormFilter($value)
{
    return str_replace('"','\\"', $value);
}

function ToArray($arr)
{
    foreach($arr as $k => $v) {
        if(is_object($v) && get_class($v) === 'DateTime') {
            $arr[$k] = Timestamp($v);
        }
    }
    return $arr;
}

function EchoTime($msg)
{
    echo time() . ': ' . Timestamp() . ': ' . $msg . PHP_EOL;
}

function time_elapsed_string($ptime)
{
    if(!is_numeric($ptime)) {
        $ptime = strtotime(Timestamp($ptime));
    }
    $etime = time() - $ptime;

    if ($etime < 1)
    {
        return 'just now';
    }

    $a = array( 365 * 24 * 60 * 60  =>  'year',
        30 * 24 * 60 * 60  =>  'month',
        24 * 60 * 60  =>  'day',
        60 * 60  =>  'hour',
        60  =>  'minute',
        1  =>  'second'
    );
    $a_plural = array( 'year'   => 'years',
        'month'  => 'months',
        'day'    => 'days',
        'hour'   => 'hours',
        'minute' => 'minutes',
        'second' => 'seconds'
    );

    foreach ($a as $secs => $str)
    {
        $d = $etime / $secs;
        if ($d >= 1)
        {
            if($secs > 30 * 24 * 60 * 60) {
                $r = number_format($d, 1);
            } else {
                $r = round($d);
            }
            return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
        }
    }
}

function echo_js($var)
{
    echo addcslashes(str_replace('"',"'", $var), "'");
}
function HTTP_SOAP($url, $host, $SOAPAction, $data)
{

    $ch = curl_init();
    $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
    $headers = [];
    $headers[] = 'POST /evernetqueryservice/evernetquery.asmx HTTP/1.1';
    $headers[] = 'Content-Length: ' . strlen($data);
    $headers[] = 'Content-Type: text/xml;charset="utf-8"';
    $headers[] = 'Host: ' . $host;
    $headers[] = 'SOAPAction: "' . $SOAPAction . '"';

    print_r($headers) . PHP_EOL;
    echo $data . PHP_EOL;

    curl_setopt($ch, CURLOPT_USERAGENT, $useragent); // For Setting the User Agent.......
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $data = curl_exec($ch);
    $e = curl_error($ch);
    if (!$e) {
        curl_close($ch);
        return $data;
    } else {
        CleanHalt([$e,$url, $host, $SOAPAction, $data]);
    }
}


function array_to_xml( $data )
{
    $xml = '';
    foreach ($data as $key => $value) {
        $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
    }

    return $xml;
}

function echo_memory_usage()
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

function exit_javascript($url, $title) {
    echo 'Redirecting to <a id="redirect_url" href="' . $url . '">' . $title . '</a><script>
    (function() {
        window.location = document.getElementById("redirect_url");
    })();
    </script>
    ';
    exit;
}

function exit_json($json)
{
    header('Content-Type: application/json');
    exit(json_encode(fix_json($json), JSON_PRETTY_PRINT));
}

function gmtime() {
    return strtotime(gmdate('m/d/Y H:i:s'));
}

function EmailDevelopers($product, $section, $summary, $message) {

}

function ShowOrDefault($var, $default = 'n/a')
{
    return $var ? htmlspecialchars_decode( $var ) : $default;
}

function LogQuery($sql, $err, $time)
{

}

function LogError($errno, $errstr, $errfile, $errline)
{

}

function __AddKmToLatLon($lat, $lon, $distance, $bearing)
{
    $earthRadius = 6371;
    $lat1 = deg2rad($lat);
    $lon1 = deg2rad($lon);
    $bearing = deg2rad($bearing);

    $lat2 = asin(sin($lat1) * cos($distance / $earthRadius) + cos($lat1) * sin($distance / $earthRadius) * cos($bearing));
    $lon2 = $lon1 + atan2(sin($bearing) * sin($distance / $earthRadius) * cos($lat1), cos($distance / $earthRadius) - sin($lat1) * sin($lat2));

    //Debug([$lat, $lon, $distance, 'lat'=>rad2deg($lat2),'lon'=>rad2deg($lon2)],null,true,false,false);
    return ['lat'=>rad2deg($lat2),'lon'=>rad2deg($lon2)];
}

function GetBoundary($lat, $lon, $distance)
{
    $res = [];

    $t = __AddKmToLatLon($lat, $lon, $distance, 0);
    $res['max_lat'] = $t['lat'];

    $t = __AddKmToLatLon($lat, $lon, $distance, 90);
    $res['max_lon'] = $t['lon'];

    $t = __AddKmToLatLon($lat, $lon, $distance, 180);
    $res['min_lat'] = $t['lat'];

    $t = __AddKmToLatLon($lat, $lon, $distance, 270);
    $res['min_lon'] = $t['lon'];

    return $res;
}

function percent2Color($value,$brightness = 255, $max = 100,$min = 0, $thirdColorHex = '00')
{
    if($value > 100) {
        $value = 100 - ($value - 100);
        if($value < 0) {
            $value = 0;
        }
    }
    // Calculate first and second color (Inverse relationship)
    $first = (1-($value/$max))*$brightness;
    $second = ($value/$max)*$brightness;

    // Find the influence of the middle color (yellow if 1st and 2nd are red and green)
    $diff = abs($first-$second);
    $influence = ($brightness-$diff)/2;
    $first = intval($first + $influence);
    $second = intval($second + $influence);

    // Convert to HEX, format and return
    $firstHex = str_pad(dechex($first),2,0,STR_PAD_LEFT);
    $secondHex = str_pad(dechex($second),2,0,STR_PAD_LEFT);

    return $firstHex . $secondHex . $thirdColorHex ;

    // alternatives:
    // return $thirdColorHex . $firstHex . $secondHex;
    // return $firstHex . $thirdColorHex . $secondHex;

}


function IsWindows()
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function xml_entities($string) {
    return strtr(
        $string,
        array(
            "<" => "&lt;",
            ">" => "&gt;",
            '"' => "&quot;",
            "'" => "&apos;",
            "&" => "&amp;",
        )
    );
}

function mysql_password_hash($input, $hex = true)
{
    $sha1_stage1 = sha1($input, true);
    $output = sha1($sha1_stage1, !$hex);
    return '*' . strtoupper($output);
} //END function mysql_password_hash



function getStartAndEndDate($week, $year)
{
    $time = strtotime("1 January $year", time());
    $day = date('w', $time);
    $time += ((7*$week)+1-$day)*24*3600;
    $return[0] = date('Y-n-j', $time);
    $time += 6*24*3600;
    $return[1] = date('Y-n-j', $time);
    return $return;
}

function GUID()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function RecID()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


/**
 * @param $num
 *
 * @return string
 */
function BigInt($num) {
    return sprintf('%.0f',  $num);
}

/**
 * @param int $time
 *
 * @return bool|string
 */

function LoadFile($filename)
{
    if(!file_exists($filename)) {
        Halt($filename . ' doesn\'t exist');
    }

    if(filesize($filename) == 0) {
        return '';
    }

    $fp = fopen($filename,'r');
    $f = fread($fp,filesize($filename));
    fclose($fp);
    return $f;
}
function Datestamp($time = 0, $null = null, $format = 'Y-m-d')
{
    if($time instanceof DateTime){
        $time = $time->getTimestamp();
        if(!$time) {
            return $null; // don't return null
        }

    }
    if(!is_numeric($time)) $time = strtotime($time);

    if(!$time) {
        if(is_null($null)) {
            $time = time();
        } else {
            return $null;
        }
    }

    if(is_array($time))
        Halt($time);

    return date($format, $time);
}

/**
 * @param $time
 *
 * @return bool|string
 */
function SolrTime($time) {
    if($time instanceof DateTime){
        $time = $time->getTimestamp();
    }

    if(!is_numeric($time))
        $time = strtotime($time);
    return date("Y-m-d\TH:i:s\Z", $time);
}

/**
 * @param      $val
 * @param bool $dollar_sign
 *
 * @return mixed|string
 */
function Currency($val, $dollar_sign = true)
{
    if($val * 1.0 == 0) {
        return '--';
    }

    $res = number_format($val * 1.0, 2);
    if($dollar_sign)
        return '$' . $res;
    return $res;
}

/**
 * @param $str
 *
 * @return mixed
 */
function escapeXml($str)
{
    $str = str_replace("&", "&amp;", $str);
    $str = str_replace(">", "&gt;", $str);
    $str = str_replace("<", "&lt;", $str);
    $str = str_replace("\"", "&quot;", $str);
    $str = str_replace("'", "&apos;", $str);

    return $str;
}

/**
 * @param $header
 */
function AltHeader($header) {
    if(!defined('NO_HEADERS'))
        header($header);
}

/**
 * @param $var
 */


if(!function_exists('money_format')) {
    /**
     * @param $format
     * @param $key
     *
     * @return mixed
     */
    function money_format($format, $key) {
        return $key;
    }
}



/**
 * @param $desc
 *
 * @return string
 */
function makeUTF($desc)
{
    $desc = UTF8_encode($desc);
    $desc = stripslashes($desc);
    return($desc);
}

/**
 * @param $date
 *
 * @return int
 */
function getTimeStampFromDBDate($date)
{
    $dateArr = explode(" ", $date);
    $datePartArr = explode("-", $dateArr[0]);
    $timePartArr = explode(":", $dateArr[1]);

    return mktime($timePartArr[0], $timePartArr[1], $timePartArr[2], $datePartArr[1], $datePartArr[2], $datePartArr[0]);
}

/**
 * @param $url
 * @param $key
 *
 * @return mixed
 */
function remove_string_var($url, $key)
{
    $url = preg_replace('/' . $key . '=[^&]+?(&)(.*)/i', '$2', $url);

    return $url;
}

/**
 * @param $arg
 * @param $replaceWith
 *
 * @return mixed
 */
function replaceSpecialChar($arg, $replaceWith)
{
    $replaceArr = ["&", "/", "\\", "*", "?", "\"", "\'", "<", ">", "|", ":", " ", "'", "#", "%"];
    $arg = str_replace($replaceArr, $replaceWith, $arg);

    return $arg;
}



/**
 * @param $orig
 *
 * @return string
 */
function base64Encode($orig)
{
    $ret = '';
    $str2 = '';
    $BASE_64_MAP_INIT = 'abHNOcd28efjABCDghiEFRSTZklmUVWXYno34567pqrGPQstuIJKLMvwxyz019';
    $str = "h0msS4cKs";
    $strlen = $str2 / 2;

    $cnt = $strlen + 2;
    $l = strlen($str);
    $str1 = substr($str, 0, $cnt);
    $str2 = substr($str, $l - $cnt, $cnt);

    $num = 908;

    $nl = "<P>" . chr(13) . chr(10);
    $max = strlen($BASE_64_MAP_INIT);
    for ($idx = 0; $idx < $max; $idx++) {
        $Base64EncMap[$idx] = substr($BASE_64_MAP_INIT, $idx, 1);
    }
    for ($idx = 0; $idx < $max; $idx++) {
        $Base64DecMap[ord($Base64EncMap[$idx])] = $idx;
    }

    if (strlen($orig) == 0) {
        $base64Encode = $str . "";
        exit;
    }
    if (is_numeric($orig)) {
        $plain = $str1 . ($orig * $num) . $str2;
    } else {
        $plain = $str1 . $orig . $str2;
    }
    $by3 = floor(strlen($plain) / 3) * 3;

    $ndx = 0;
    do {
        $first = ord(substr($plain, $ndx + 0, 1));
        $second = ord(substr($plain, $ndx + 1, 1));
        $third = ord(substr($plain, $ndx + 2, 1));

        if ($second == "0" || $first == "0" || $third == "0") {
            break;
        }

        $ret = $ret . $Base64EncMap[($first / 4) & 63];
        $ret = $ret . $Base64EncMap[(($first * 16) & 48) + (($second / 16) & 15)];
        $ret = $ret . $Base64EncMap[(($second * 4) & 60) + (($third / 64) & 3)];
        $ret = $ret . $Base64EncMap[$third & 63];

        $ndx = $ndx + 3;
    } while ($ndx <= $by3);

    if ($by3 < strlen($plain)) {
        $first = ord(substr($plain, $ndx + 0, 1));
        $ret = $ret . $Base64EncMap[($first / 4) & 63];
        if ((strlen($plain) % 3) == 2) {
            $second = ord(substr($plain, $ndx + 1, 1));
            $ret = $ret . $Base64EncMap[(($first * 16) & 48) + (($second / 16) & 15)];
            $ret = $ret . $Base64EncMap[(($second * 4) & 60)];
        } else {
            $ret = $ret . $Base64EncMap[($first * 16) & 48];
            $ret = $ret . "/";
        }

        $ret = $ret . "/";
    }

    return $ret;
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

    if ($print !== false) {
        echo $finalMsg;
    } else {
        //LogError(-1,$finalMsg,'','');
    }

    if($exit)
        exit();
}

function Numeric($val)
{
    $res = trim(preg_replace('/[^0-9\.-]/si','',$val) * 1.0);
    if(!$res) {
        return $val;
    }
    return $res;
}

function NumericPhone($val)
{
    $res = trim(preg_replace('/[^0-9]/si','',$val) * 1.0);
    if(!$res) {
        return $val;
    }
    return $res;
}

function phone_number($val)
{
    if (preg_match('/^\+?\d?(\d{3})(\d{3})(\d{4})$/', $val, $matches)) {
        $result = $matches[1] . '-' . $matches[2] . '-' . $matches[3];

        return $result;
    }
    return $val;
}


function run_in_background($cmd)
{
    if(IsWindows())
    {
        pclose(popen("start " . $cmd, "r"));
        return 0;
    }

    $PID = exec("nohup $cmd 1>/dev/null & echo $!");
    return($PID);
}

function Age($time) {
    if(!is_numeric($time)) {
        $time = strtotime($time);
    }
    $diff = time() - $time;

    if($diff < 15 * 60) {
        return floor(($diff + 30) / 60) . ' minutes';
    }

    if($diff / 3600 < 24)
        return ceil($diff / 3600) . ' hours';

    return ceil($diff / 3600 / 24 - 0.5) . ' days';
}

function GetPlaceholders($count)
{
    return implode(',', array_fill(0, $count, '{{}}'));
}

function WordCount($val) {
    return sizeof(explode(' ', preg_replace('/\s+/si',' ',$val)));
}

function Base16to10($hex)
{
    return base_convert($hex,16,10);
}


function MD5toBase62($md5)
{
    $o = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $a = Base16to10(substr($md5,0,8));
    $b = Base16to10(substr($md5,8,8));

    $c = abs(($a * 1.0) ^ ($b * 1.0));

    $str = '';
    $m = strlen($o);
    while($c > 1)
    {
        $str .= $o[intval($c % $m)];
        $c = $c / $m;
    }
    $str .= $o[intval($c * $m)];

	$a = Base16to10(substr($md5,16,8));
	$b = Base16to10(substr($md5,24,8));
    $c = abs(($a * 1.0) ^ ($b * 1.0));
    $m = strlen($o);
    while($c > 1)
    {
        $str .= $o[intval($c % $m)];
        $c = $c / $m;
    }
    $str .= $o[intval($c * $m)];


    return $str;
}


function Truncate($str, $length, $words = false)
{
    if(strlen($str) > $length) {
        if($words) {
            $s = strpos($str,' ', $length);
            return substr($str, 0, $s) . '...';
        } else {
            return substr($str, 0, $length) . '...';
        }
    }
    return $str;
}

function fix_json($json)
{
    foreach ($json as $i => $row) {
        if (is_array($row)) {
            $json[$i] = fix_json($row);
        } else {
            if(is_object($json[$i])) {
                if($json[$i] instanceof DateTime){
                    $json[$i] = SolrTime($json[$i]);
                } else {
                    Halt($json[$i]);
                }
            }
            $json[$i] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($json[$i]));
        }
    }
    return $json;
}


/**
 * @param $col_type
 *
 * @return string
 */
function ColumnTypeToProperty($col_type)
{
	switch(strtolower($col_type))
	{
        case 'varchar':
        case 'char':
            return 'string';

		case 'tinyint unsigned':
		case 'bigint unsigned':
		case 'int unsigned':
			return 'uint';

        case 'numeric':
            return 'int';
	}
	return $col_type;
}

/**
 * @param $txt
 *
 * @return mixed
 */
function InlineSafe($txt)
{
	$txt = str_replace('"','\\"',str_replace("'","\\'",$txt));
	$txt = str_replace("\r","",$txt);
	$txt = str_replace("\n","<br/>",$txt);
	return $txt;
}

/**
 * @param     $string
 * @param int $length
 *
 * @return string
 */
function TrimString($string, $length = 150)
{
	$string = trim(preg_replace('/\s+/',' ', $string));
	$string = strip_tags($string);
	if (strlen($string) <= $length) {
		$string = $string; //do nothing
	} else {
		$string = substr($string, 0, strpos(substr($string, 0, $length), ' ')) . '...';
	}

	return $string;
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

/**
 * @param     $timeStamp
 * @param int $totalMonths
 *
 * @return int
 */
function addMonthToDate($timeStamp, $totalMonths=1)
{
	if(!is_numeric($timeStamp))
		$timeStamp = strtotime($timeStamp);

	// You can add as many months as you want. mktime will accumulate to the next year.
	$thePHPDate = getdate($timeStamp); // Covert to Array
	$thePHPDate['mon'] = $thePHPDate['mon']+$totalMonths; // Add to Month
	$timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']); // Convert back to timestamp
	return $timeStamp;
}

/**
 * @param $text
 *
 * @return mixed
 */
function ToSearchable($text)
{
	return preg_replace('/[^a-z0-9]/si', '', strtolower($text));
}

/**
 * @param $num
 *
 * @return float|int
 */
function NumberOfZeros($num)
{
	return $num != 0 ? floor(log10(abs($num))) : 1;

}

/**
 * @param     $timeStamp
 * @param int $totalDays
 *
 * @return int
 */
function addDayToDate($timeStamp, $totalDays=1)
{
	if(!is_numeric($timeStamp))
		$timeStamp = strtotime($timeStamp);

	// You can add as many days as you want. mktime will accumulate to the next month / year.
	$thePHPDate = getdate($timeStamp);
	$thePHPDate['mday'] = $thePHPDate['mday']+$totalDays;
	$timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
	return $timeStamp;
}

/**
 * @param     $timeStamp
 * @param int $totalYears
 *
 * @return int
 */
function addYearToDate($timeStamp, $totalYears=1)
{
	if(!is_numeric($timeStamp))
		$timeStamp = strtotime($timeStamp);

	$thePHPDate = getdate($timeStamp);
	$thePHPDate['year'] = $thePHPDate['year']+$totalYears;
	$timeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
	return $timeStamp;
}

// http://webdesign.anmari.com/1956/calculate-date-from-day-of-year-in-php/
/**
 * @param $year
 * @param $DayInYear
 *
 * @return bool|string
 */
function dayofyear2date( $year, $DayInYear )
{
	$d = new DateTime($year.'-01-01');
	date_modify($d, '+'.($DayInYear-1).' days');
	return Datestamp($d->getTimestamp());
}

/**
 * @param $date
 *
 * @return array
 */
function x_week_range($date) {
	$ts = strtotime($date);
	$start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
	return [date('Y-m-d', $start),
			date('Y-m-d', strtotime('next saturday', $start))];
}

/**
 * @param      $date
 * @param bool $debug
 *
 * @return array
 */
function CalcWeek($date, $debug = false)
{
	if(strtotime($date) == 0)
	{
		[null,null,null];
	}

	list($start_date, $end_date) = x_week_range($date);

	$t = strtotime($date);
	$y = date('Y', $t);
	$m = date('m', $t);



	if($debug) {
		print_r(['date'=>$date, 'y'=>$y,'m'=>$m]);
	}
	$week_date = $start_date;
	$month_year = $m . $y;

	$w = date('W', strtotime($week_date));

	$week_year = $w . $y;
	return [$week_date, $month_year, $week_year];
}

/**
 * @param $err
 */
function RedirectError($err, $url = '/')
{
	global $Session;
	$Session->error = $err;
	if(isset($_SERVER['HTTP_REFERER']))
		header('location: ' . $_SERVER['HTTP_REFERER']);
	else {
        header('location: ' . $url);
    }
	exit();
}

function RedirectNotice($err, $url = '/')
{
    global $Session;
    $Session->notice = $err;
    if(isset($_SERVER['HTTP_REFERER']))
        header('location: ' . $_SERVER['HTTP_REFERER']);
    else {
        header('location: ' . $url);
    }
    exit();
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
 * @param $field
 *
 * @return string
 */
function FieldToDisplay($field)
{
	$t = ucwords(implode(' ',explode('_',$field)));
	$t = str_replace(' ','',$t);
	if(strcasecmp(substr($t,-2),'id')==0)
		$t = substr($t,0,strlen($t)-2);
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
	preg_match_all('/[A-Z\d][^A-Z\d]*/',$str,$results);
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

/**
 * @param $number
 *
 * @return string
 */
function PhoneNumber($number)
{
    if(!$number) {
        return '';
    }

    $number = preg_replace('/[^0-9]/si','',$number);

	$m = strlen($number);
	$last = substr($number,$m-4,4);
	if($m-7 >= 0)
		$mid = substr($number,$m-7,3);
	else $mid = 0;
	if($m-10 >= 0)
		$area = substr($number,$m-10,3);
	else $area = '';

	if($m - 10 > 0)
		$plus = '+' . substr($number,0,$m-10);
	else
		$plus = '';
	return $plus . '(' . $area . ') ' . $mid . '-' . $last;
}

/**
 * @param        $count
 * @param string $params
 * @param null   $_SORT_BY
 * @param null   $_SORT_DIR
 *
 * @return string
 */
function PaginationLinks($count,$params = null, $_SORT_BY = null, $_SORT_DIR = null, $show_view_all = true, $per_page = null)
{
    if(is_null($per_page)) {
        $per_page = PER_PAGE;
    }

    if($params == null) {
        $params = [];
        foreach($_GET as $k => $v) {
            if(!in_array($k,['sort_by','dir','page','per_page'])) {
                $params[] = $k . '=' . $v;
            }
        }
    }
	$_SORT_BY = $_SORT_BY ? $_SORT_BY : SORT_BY;
	$_SORT_DIR = $_SORT_DIR ? $_SORT_DIR : SORT_DIR;

	if(is_array($params)) {
        $params = implode('&', $params);
    }



    if($per_page > 0)
	{
		$num_pages = ceil( $count / $per_page);
		if($num_pages <= 1) return '';

		$start_page = PAGE - 10;
		$end_page = PAGE + 10;
		if($start_page < 0)
			$start_page = 0;
		if($start_page >= $num_pages)
			$start_page = $num_pages - 1;
		if($end_page < 0)
			$end_page = 0;
		if($end_page >= $num_pages)
			$end_page = $num_pages - 1;

		$html = '<p><ul class="pagination">';
		if(PAGE > 10)
		{
			$html .= '<li class="first"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (0) . '&per_page=' . $per_page . '&' . $params . '">&lt;&lt;</a></li>';
			$html .= '<li class="previous"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE - 10) . '&per_page=' . $per_page . '&' . $params . '">&lt;</a></li>';
		}

		for($j = $start_page; $j <= $end_page; $j++)
		{
			if($j != PAGE)
				$html .= '<li class="page_number"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j . '&per_page=' . $per_page . '&' . $params . '">' . ($j+1) . '</a></li>';
			else
				$html .= '<li class="page_number_selected"><span>' . ($j+1) . '</span></li>';
		}
		if(PAGE < $num_pages - 10 && $num_pages > 10)
		{
			$html .= '<li class="next"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE + 10) . '&per_page=' . $per_page . '&' . $params . '">&gt;</a></li>';
			$html .= '<li class="last"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . ($num_pages - 1) . '&per_page=' . $per_page . '&' . $params . '">&gt;&gt;</a></li>';
		}

        if($show_view_all) {
            $html .= '<li class="view_all"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j
                . '&per_page=0&' . $params . '">View All</a></li>';
        }
		return $html . '</ul></p>';
	}
	$html = '<br/><ul class="pagination">';
    if($show_view_all) {
        $html .= '<li class="view_all"><a href="' . CURRENT_PAGE . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=0&' . $params
            . '">View Paginated</a></li>';
    }
	return $html . '</ul><br/>';
}

/**
 * @param $datetime
 *
 * @return int
 */
function iCalDate2TimeStamp($datetime)
{
    $output = mktime( $datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year'] );
    return $output;
}

/**
 * @param $date
 *
 * @return bool|string
 */
function FancyDateTime($date)
{
	if(!is_numeric($date))
	{
		if(($date = strtotime($date)) === false)
			return '<i>Invalid Date</i>';
	}
	if($date == 0)
		return '<i>Not Set</i>';
	return date('F jS, Y g:iA', $date);
}

/**
 * @param $date
 *
 * @return bool|string
 */
function FancyDate($date)
{
	if(is_null($date))
		return '<i>Not Set</i>';

	if(!is_numeric($date)) {
	    $date = Timestamp($date);
        $date = strtotime($date);
    }

	if($date == 0)
		return '<i>Not Set</i>';

	return date('F jS, Y', $date);
}

/**
 * @param $date
 *
 * @return bool|string
 */
function ShortDate($date)
{
	if(is_null($date))
		return '<i>Not Set</i>';

	if(!is_numeric($date))
		$date = strtotime($date);

	if($date == 0)
		return '<i>Not Set</i>';

	return date('n/j/y', $date);
}


/**
 * @param $serialized
 *
 * @return array
 */
function PostFromSerialized($serialized)
{
	$reqs = explode('&',$serialized);
	$post = [];
	foreach($reqs as $req)
	{
		$nk = explode('=',$req);
		$nk[0] = urldecode($nk[0]);
		if(substr($nk[0],-2) === '[]')
		{
			$nk[0] = substr($nk[0],0,strlen($nk[0]) - 2);
			$post[$nk[0]][] = urldecode($nk[1]);
		}
		else
			$post[$nk[0]] = isset($nk[1]) ? urldecode($nk[1]) : '';
	}
	return $post;
}

/**
 * @param $start_at
 * @param $end_at
 *
 * @return string
 */
function HourMinDiff($start_at, $end_at)
{
	if(!is_numeric($start_at)) $start_at = strtotime($start_at);
	if(!is_numeric($end_at)) $end_at = strtotime($end_at);

	$hours = floor(($end_at - $start_at) / 3600);
	$mins = ceil((($end_at - $start_at) / 3600 - $hours) * 60);
	return $hours . ':' . ($mins < 10 ? '0' : '') . $mins;
}

/**
 * @param int $time
 *
 * @return int
 */
function AdjustedTime($time = 0)
{
	if(!$time) $time = time();

    if(!is_numeric($time)) $time = strotime($time);

	return $time;
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function StandardDate($time = 0, $null = null, $last_month = null, $last_year = null)
{
	if(!is_numeric($time)) {
        $time = Timestamp($time, $null);
    }

	if(!$time) {
        if (is_null($null)) {
            $time = AdjustedTime();
        } else {
            return $null;
        }
    }

    $time = strtotime($time);

    if(!is_null($last_month) && !is_null($last_year)) {
        $m = date('m', $time);
        $y = date('Y', $time);

        if ($m == $last_month && $y == $last_year) {
            return date('j', $time);
        }

        if ($y == $last_year) {
            return date('n/j', $time);
        }
    }
	return date('n/j/Y', $time);
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function DayMonthDate($time = 0, $null = null)
{
	if(!is_numeric($time)) $time = strtotime($time);
	if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
	return date('n-j', $time);
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function StandardDateTime($time = 0, $null = null, $debug  = false)
{
	if(!is_numeric($time)) {
        $time = strtotime(Timestamp($time, $null, $debug));
    }
	if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
	return date('n/j/Y h:i A', $time);
}

function StandardTime($time = 0, $null = null)
{
    if(!is_numeric($time)) $time = strtotime($time);
    if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
    return date('h:iA', $time);
}

/**
 * @param $text
 *
 * @return string
 */
function StringToHTML($text)
{
	$text = str_replace("\r",'',$text);
	$text = preg_replace('/\n+/si',"\n", $text);
	$t = explode("\n",$text);
	return '<p>' . implode("</p><p>", $t) . '</p>';
}

/**
 * @param $text
 *
 * @return string
 */
function StringToBR($text)
{
	$text = str_replace("\r",'',$text);
	$t = explode("\n",$text);
	return implode("<br/>", $t);
}


function FromUserTimeToGMT($time) {
    global $User;

    if(!is_numeric($time)) {
        $time = strtotime($time);
    }

    return Timestamp($time - $User->hours_diff * 3600);
}

/**
 * @param int $time
 *
 * @return bool|string
 */
function Timestamp($time = 0, $null = null, $debug = false)
{
    if($time instanceof DateTime){
        $time = $time->getTimestamp();
        if($debug) {
            CleanHalt(date('Y-m-d', $time));
        }
        if(!$time) {
            return ''; // don't return null
        }
    }

	if($time && !is_numeric($time)) {
        $time = strtotime($time);
    }

	if($time == 0) {
        if(!is_null($null)) {
            return $null;
        }
        $time = AdjustedTime();
    }
	return date('Y-m-d H:i:s', $time);
}

/**
 * @param int  $time
 * @param null $null
 *
 * @return bool|null|string
 */
function TimeOnlystamp($time = 0, $null = null)
{
	if(!is_numeric($time)) $time = strtotime($time);
	if($time == 0) if(is_null($null)) $time = AdjustedTime(); else return $null;
	return date('H:i', $time);
}


/**
 * @param        $num
 * @param int    $dec
 * @param string $null
 *
 * @return string
 */
function smart_number_format($num, $dec = 2, $null = '-')
{
	if(!is_numeric($dec))
		return $num;

	if(!is_numeric($num) || !$num)
		return $null;
	return number_format($num, $dec);
}

/**
 * @param     $num
 * @param int $dec
 *
 * @return string
 */
function form_number_format($num, $dec = 2, $comma = '')
{
	if(!is_numeric($num))
		return $num;
	return number_format($num, $dec,'.',$comma);
}

function StringRepeatCS($pattern, $multiplier) {
    $t = [];
    for($j = 0; $j < $multiplier; $j++) {
        $t[] = $pattern;
    }
    return implode(',', $t);
}

function createQuickList($array, $accessor = '$property')
{
    $t = array_keys($array);

    foreach($t as $v) {

        $name = ucwords(strtolower($v));
        echo "<li><?php show('$name', $accessor->$v); ?></li>\n";
    }
}

function getTinyUrl($url)  {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function isSecure() {
    if(!isset($_SERVER['HTTPS'])) {
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
function BootstrapPaginationLinks($count,$params = null, $_SORT_BY = null, $_SORT_DIR = null, $_PER_PAGE = null, $_URL = null)
{
    if($params == null) {
        $params = [];
        foreach($_GET as $k => $v) {
            if(!in_array($k,['sort_by','dir','page','per_page'])) {
                $params[] = $k . '=' . $v;
            }
        }
    }
    if(is_array($params)) {
        $params = implode('&', $params);
    }


    $_SORT_BY = $_SORT_BY ? $_SORT_BY : SORT_BY;
    $_SORT_DIR = $_SORT_DIR ? $_SORT_DIR : SORT_DIR;
    $_PER_PAGE = $_PER_PAGE ? $_PER_PAGE : PER_PAGE;
    $_URL = $_URL ? $_URL : CURRENT_PAGE;

    if($_PER_PAGE > 0)
    {
        $num_pages = ceil( $count / $_PER_PAGE);
        if($num_pages <= 1) return '';

        $start_page = PAGE - 10;
        $end_page = PAGE + 10;
        if($start_page < 0)
            $start_page = 0;
        if($start_page >= $num_pages)
            $start_page = $num_pages - 1;
        if($end_page < 0)
            $end_page = 0;
        if($end_page >= $num_pages)
            $end_page = $num_pages - 1;

        $html = '<ul class="pagination">';
        if(PAGE > 10)
        {
            $html .= '<li class="first"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (0) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&lt;&lt;</a></li>';
            $html .= '<li class="previous"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . (PAGE - 10) . '&per_page=' . $_PER_PAGE . '&' . $params . '">&lt;</a></li>';
        }

        for($j = $start_page; $j <= $end_page; $j++)
        {
            if($j != PAGE)
                $html .= '<li class="page_number"><a href="' . $_URL . '?sort_by=' . $_SORT_BY . '&dir=' . $_SORT_DIR . '&page=' . $j . '&per_page=' . $_PER_PAGE . '&' . $params . '">' . ($j+1) . '</a></li>';
            else
                $html .= '<li class="page_number"><a href="#">' . ($j+1) . '</a></li>';
        }
        if(PAGE < $num_pages - 10 && $num_pages > 10)
        {
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
