<?php

class Strings extends SafeClass
{
    /**
     * @param $str
     * @return null|string|string[]
     */
    public static function KeyboardOnly($str)
    {
        $str = preg_replace('/[^a-z0-9\!\@\#\$\%\^\&\*\(\)\-\=\_\+\[\]\\\{\}\|\;\'\:\"\,\.\/\<\>\\\?\ ]/si','', $str);
        return preg_replace('/\s+/si',' ', $str);
    }

    public static function SimpleXMLToArray($xml)
    {
        $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);
        return $array;
    }

    /**
     * @param $XML
     * @return mixed
     */
    public static function XMLtoArray($XML)
    {
        $xml_array = '';
        $multi_key2 = [];
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $XML, $vals);
        xml_parser_free($xml_parser);
        // wyznaczamy tablice z powtarzajacymi sie tagami na tym samym poziomie
        $_tmp = '';
        foreach ($vals as $xml_elem) {
            $x_tag = $xml_elem['tag'];
            $x_level = $xml_elem['level'];
            $x_type = $xml_elem['type'];
            if ($x_level != 1 && $x_type == 'close') {
                if (isset($multi_key[$x_tag][$x_level]))
                    $multi_key[$x_tag][$x_level] = 1;
                else
                    $multi_key[$x_tag][$x_level] = 0;
            }
            if ($x_level != 1 && $x_type == 'complete') {
                if ($_tmp == $x_tag)
                    $multi_key[$x_tag][$x_level] = 1;
                $_tmp = $x_tag;
            }
        }
        // jedziemy po tablicy
        foreach ($vals as $xml_elem) {
            $x_tag = $xml_elem['tag'];
            $x_level = $xml_elem['level'];
            $x_type = $xml_elem['type'];
            if ($x_type == 'open')
                $level[$x_level] = $x_tag;
            $start_level = 1;
            $php_stmt = '$xml_array';
            if ($x_type == 'close' && $x_level != 1)
                $multi_key[$x_tag][$x_level]++;
            while ($start_level < $x_level) {
                $php_stmt .= '[$level[' . $start_level . ']]';
                if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level])
                    $php_stmt .= '[' . ($multi_key[$level[$start_level]][$start_level] - 1) . ']';
                $start_level++;
            }
            $add = '';
            if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type == 'open' || $x_type == 'complete')) {
                if (!isset($multi_key2[$x_tag][$x_level]))
                    $multi_key2[$x_tag][$x_level] = 0;
                else
                    $multi_key2[$x_tag][$x_level]++;
                $add = '[' . $multi_key2[$x_tag][$x_level] . ']';
            }
            if (isset($xml_elem['value']) && trim($xml_elem['value']) != '' && !array_key_exists('attributes', $xml_elem)) {
                if ($x_type == 'open')
                    $php_stmt_main = $php_stmt . '[$x_type]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
                else
                    $php_stmt_main = $php_stmt . '[$x_tag]' . $add . ' = $xml_elem[\'value\'];';
                eval($php_stmt_main);
            }
            if (array_key_exists('attributes', $xml_elem)) {
                if (isset($xml_elem['value'])) {
                    $php_stmt_main = $php_stmt . '[$x_tag]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
                    eval($php_stmt_main);
                }
                foreach ($xml_elem['attributes'] as $key => $value) {
                    $php_stmt_att = $php_stmt . '[$x_tag]' . $add . '[$key] = $value;';
                    eval($php_stmt_att);
                }
            }
        }
        return $xml_array;
    }


    public static function EndsWith($string, $ends_with)
    {
        return substr($string, -strlen($ends_with), strlen($ends_with)) === $ends_with;
    }

    /**
     * @param $remove
     * @param $string
     * @return bool|string
     */
    public static function RemoveFromStart($remove, $string)
    {
        $remove_length = strlen($remove);

        return substr($string, $remove_length, strlen($string) - $remove_length);
    }

    /**
     * @param int $err_code
     * @return string
     */
    public static function JSONErrorCodeToString($err_code)
    {
        switch ($err_code) {
            case JSON_ERROR_NONE:
                return ' - No errors';
            case JSON_ERROR_DEPTH:
                return ' - Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return ' - Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return ' - Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return ' - Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            case JSON_ERROR_RECURSION:
                return ' - One or more recursive references in the value to be encoded';
            case JSON_ERROR_INF_OR_NAN:
                return ' - One or more NAN or INF values in the value to be encoded';
            case JSON_ERROR_UNSUPPORTED_TYPE:
                return ' - 	A value of a type that cannot be encoded was given';
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                return ' - A property name that cannot be encoded was given';
            case JSON_ERROR_UTF16:
                return ' - Malformed UTF-16 characters, possibly incorrectly encoded';
            default:
                return ' - Unknown error';
        }
    }

    public static function FormFilter($value)
    {
        return str_replace('"', '\\"', $value);
    }

    public static function EchoJS($js)
    {
        return addcslashes(str_replace('"', "'", $js), "'");
    }

    public static function ArrayToXML($data)
    {
        $xml = '';
        foreach ($data as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }

        return $xml;
    }

    public static function PercentToColor($value, $brightness = 255, $max = 100, $min = 0, $thirdColorHex = '00')
    {
        if ($value > $max) {
            $value = $max - ($value - $max);
            if ($value < $min) {
                $value = $min;
            }
        }
        // Calculate first and second color (Inverse relationship)
        $first = (1 - (($value - $min) / ($max - $min))) * $brightness;
        $second = (($value - $min) / ($max - $min)) * $brightness;

        // Find the influence of the middle color (yellow if 1st and 2nd are red and green)
        $diff = abs($first - $second);
        $influence = ($brightness - $diff) / 2;
        $first = intval($first + $influence);
        $second = intval($second + $influence);

        // Convert to HEX, format and return
        $firstHex = str_pad(dechex($first), 2, 0, STR_PAD_LEFT);
        $secondHex = str_pad(dechex($second), 2, 0, STR_PAD_LEFT);

        return $firstHex . $secondHex . $thirdColorHex;

        // alternatives:
        // return $thirdColorHex . $firstHex . $secondHex;
        // return $firstHex . $thirdColorHex . $secondHex;

    }

    public static function XMLEntities($string)
    {
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

    public static function BigInt($num)
    {
        return sprintf('%.0f', $num);
    }

    /**
     * @param      $val
     * @param bool $dollar_sign
     *
     * @return mixed|string
     */
    public static function Currency($val, $dollar_sign = true, $sig_figs = 2)
    {
        if ($val * 1.0 == 0) {
            return '--';
        }

        $res = number_format($val * 1.0, $sig_figs);
        if ($dollar_sign)
            return '$' . $res;
        return $res;
    }

    /**
     * @param $str
     *
     * @return mixed
     */
    public static function EscapeXml($str)
    {
        $str = str_replace("&", "&amp;", $str);
        $str = str_replace(">", "&gt;", $str);
        $str = str_replace("<", "&lt;", $str);
        $str = str_replace("\"", "&quot;", $str);
        $str = str_replace("'", "&apos;", $str);

        return $str;
    }

    /**
     * @param $desc
     *
     * @return string
     */
    public static function MakeUTF($desc)
    {
        $desc = UTF8_encode($desc);
        $desc = stripslashes($desc);
        return ($desc);
    }

    /**
     * @param $url
     * @param $key
     *
     * @return mixed
     */
    public static function RemoveStringVar($url, $key)
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
    public static function ReplaceSpecialChar($arg, $replaceWith)
    {
        $replaceArr = ["&", "/", "\\", "*", "?", "\"", "\'", "<", ">", "|", ":", " ", "'", "#", "%"];
        $arg = str_replace($replaceArr, $replaceWith, $arg);

        return $arg;
    }

    public static function Numeric($val)
    {
        $res = trim(preg_replace('/[^0-9\.-]/si', '', $val) * 1.0);
        if (!$res) {
            return $val * 1.0;
        }
        return $res;
    }

    public static function NumbersOnly($val)
    {
        $res = trim(preg_replace('/[^0-9]/si', '', $val) * 1.0);
        if (!$res) {
            return $val * 1.0;
        }
        return $res;
    }

    public static function NumericPhone($val)
    {
        $res = trim(preg_replace('/[^0-9]/si', '', $val) * 1.0);
        if (!$res) {
            return $val;
        }
        return $res;
    }

    public static function PhoneNumber($val)
    {
        if (preg_match('/^\+?\d?(\d{3})(\d{3})(\d{4})$/', $val, $matches)) {
            $result = $matches[1] . '-' . $matches[2] . '-' . $matches[3];

            return $result;
        }
        return $val;
    }

    public static function GetPlaceholders($count, $str = '{{}}')
    {
        return implode(',', array_fill(0, $count, $str));
    }

    public static function GetSQLServerPlaceholders($count)
    {
        return self::GetPlaceholders($count, '@');
    }

    public static function WordCount($val)
    {
        return sizeof(explode(' ', preg_replace('/\s+/si', ' ', $val)));
    }

    public static function Base16to10($hex)
    {
        return base_convert($hex, 16, 10);
    }


    public static function MD5toBase62($md5)
    {
        $o = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $a = Base16to10(substr($md5, 0, 8));
        $b = Base16to10(substr($md5, 8, 8));

        $c = abs(($a * 1.0) ^ ($b * 1.0));

        $str = '';
        $m = strlen($o);
        while ($c > 1) {
            $str .= $o[intval($c % $m)];
            $c = $c / $m;
        }
        $str .= $o[intval($c * $m)];

        $a = Base16to10(substr($md5, 16, 8));
        $b = Base16to10(substr($md5, 24, 8));
        $c = abs(($a * 1.0) ^ ($b * 1.0));
        $m = strlen($o);
        while ($c > 1) {
            $str .= $o[intval($c % $m)];
            $c = $c / $m;
        }
        $str .= $o[intval($c * $m)];


        return $str;
    }

    public static function Truncate($str, $length, $words = false)
    {
        if (strlen($str) > $length) {
            if ($words) {
                $s = strpos($str, ' ', $length);
                return substr($str, 0, $s) . '...';
            } else {
                return substr($str, 0, $length) . '...';
            }
        }
        return $str;
    }

    public static function FixJSON($json)
    {
        if (!is_array($json)) {
            return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($json));
        }

        foreach ($json as $i => $row) {
            if (is_array($row)) {
                $json[$i] = Strings::FixJSON($row);
            } else {
                if (is_object($json[$i])) {
                    if ($json[$i] instanceof DateTime) {
                        $json[$i] = Dates::SolrTime($json[$i]);
                    } else {
                        if ($json[$i] instanceof SafeClass) {
                            $json[$i] = $json[$i]->ToArray();
                        } else {
                            Halt(['error' => 'fix_json unknown object', $json[$i]]);
                        }
                    }
                }
                if (!is_array($json[$i])) {
                    $json[$i] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($json[$i]));
                }
            }
        }
        return $json;
    }


    /**
     * @param $txt
     *
     * @return mixed
     */
    public static function InlineSafe($txt)
    {
        $txt = str_replace('"', '\\"', str_replace("'", "\\'", $txt));
        $txt = str_replace("\r", "", $txt);
        $txt = str_replace("\n", "<br/>", $txt);
        return $txt;
    }

    /**
     * @param     $string
     * @param int $length
     *
     * @return string
     */
    public static function TrimString($string, $length = 150)
    {
        $string = trim(preg_replace('/\s+/', ' ', $string));
        $string = strip_tags($string);
        if (strlen($string) <= $length) {
            return $string;
        }
            $string = substr($string, 0, strpos(substr($string, 0, $length), ' ')) . '...';

        return $string;
    }


    /**
     * @param $text
     *
     * @return mixed
     */
    public static function ToSearchable($text)
    {
        return preg_replace('/[^a-z0-9]/si', '', strtolower($text));
    }

    /**
     * @param $num
     *
     * @return float|int
     */
    public static function NumberOfZeros($num)
    {
        return $num != 0 ? floor(log10(abs($num))) : 1;

    }


    /**
     * @param $number
     *
     * @return string
     */
    public static function PhoneNumber2($number)
    {
        if (!$number) {
            return '';
        }

        $number = preg_replace('/[^0-9]/si', '', $number);

        $m = strlen($number);
        $last = substr($number, $m - 4, 4);
        if ($m - 7 >= 0)
            $mid = substr($number, $m - 7, 3);
        else $mid = 0;
        if ($m - 10 >= 0)
            $area = substr($number, $m - 10, 3);
        else $area = '';

        if ($m - 10 > 0)
            $plus = '+' . substr($number, 0, $m - 10);
        else
            $plus = '';
        return $plus . '(' . $area . ') ' . $mid . '-' . $last;
    }


    /**
     * @param $text
     *
     * @return string
     */
    public static function StringToHTML($text)
    {
        $text = str_replace("\r", '', $text);
        $text = preg_replace('/\n+/si', "\n", $text);
        $t = explode("\n", $text);
        return '<p>' . implode("</p><p>", $t) . '</p>';
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function StringToBR($text)
    {
        $text = str_replace("\r", '', $text);
        $t = explode("\n", $text);
        return implode("<br/>", $t);
    }


    /**
     * @param        $num
     * @param int $dec
     * @param string $null
     *
     * @return string
     */
    public static function SmartNumberFormat($num, $dec = 2, $null = '-')
    {
        if (!is_numeric($dec))
            return $num;

        if (!is_numeric($num) || !$num)
            return $null;
        return number_format($num, $dec);
    }

    /**
     * @param     $num
     * @param int $dec
     *
     * @return string
     */
    public static function FormNumberFormat($num, $dec = 2, $comma = '')
    {
        if (!is_numeric($num))
            return $num;
        return number_format($num, $dec, '.', $comma);
    }

    public static function StringRepeatCS($pattern, $multiplier)
    {
        $t = [];
        for ($j = 0; $j < $multiplier; $j++) {
            $t[] = $pattern;
        }
        return implode(',', $t);
    }

    public static function CreateQuickList($array, $accessor = '$item', $function = 'Show')
    {
        $t = array_keys($array);
        $res = '';
        foreach ($t as $v) {

            $name = ucwords(strtolower($v));
            $res .= "<li><?php " . $function . "('$name', $accessor->$v); ?></li>\n";
        }
        return $res;
    }

    public static function ShowOrDefault($var, $default = 'n/a')
    {
        return $var ? htmlspecialchars_decode($var) : $default;
    }
}

function FormFilter($value)
{
    return Strings::FormFilter($value);
}

function echo_js($var)
{
    return Strings::EchoJS($var);
}


function array_to_xml($data)
{
    return Strings::ArrayToXML($data);
}

function percent2Color($value, $brightness = 255, $max = 100, $min = 0, $thirdColorHex = '00')
{
    return Strings::PercentToColor($value, $brightness, $max, $min, $thirdColorHex);
}

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

function Numeric($val)
{
    return Strings::Numeric($val);
}

function NumericPhone($val)
{
    return NumericPhone($val);
}

function phone_number($val)
{
    return Strings::PhoneNumber($val);
}

function GetPlaceholders($count)
{
    return Strings::GetPlaceholders($count);
}

function WordCount($val)
{
    return Strings::WordCount($val);
}

function Base16to10($hex)
{
    return Strings::Base16to10($hex);
}


function MD5toBase62($md5)
{
    return Strings::MD5toBase62($md5);
}

function Truncate($str, $length, $words = false)
{
    return Strings::Truncate($str, $length, $words);
}

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

function StringRepeatCS($pattern, $multiplier)
{
    return Strings::StringRepeatCS($pattern, $multiplier);
}

function createQuickList($array, $accessor = '$item', $function = 'Show')
{
    return Strings::CreateQuickList($array, $accessor, $function);
}

function ShowOrDefault($var, $default = 'n/a')
{
    return Strings::ShowOrDefault($var, $default);
}
