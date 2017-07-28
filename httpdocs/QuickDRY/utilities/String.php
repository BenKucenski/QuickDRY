<?php
function FormFilter($value)
{
    return str_replace('"','\\"', $value);
}


function echo_js($var)
{
    echo addcslashes(str_replace('"',"'", $var), "'");
}


function array_to_xml( $data )
{
    $xml = '';
    foreach ($data as $key => $value) {
        $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
    }

    return $xml;
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


/**
 * @param $num
 *
 * @return string
 */
function BigInt($num) {
    return sprintf('%.0f',  $num);
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
    if(!is_array($json)) {
        return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($json));
    }

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

function ShowOrDefault($var, $default = 'n/a')
{
    return $var ? htmlspecialchars_decode( $var ) : $default;
}
