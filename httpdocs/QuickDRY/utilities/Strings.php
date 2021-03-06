<?php

/**
 * Class Strings
 */
class Strings extends SafeClass
{
    /**
     * @param $bin
     * @return false|string
     */
    public static function NVarChar($bin)
    {
        // you must also CAST([Column] AS VARBINARY(MAX)) AS Column
        //Binary to hexadecimal
        $hex = bin2hex($bin);

        //And then from hex to string
        $str = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }

        //And then from UCS-2LE/SQL_Latin1_General_CP1_CI_AS (that's the column format in the DB) to UTF-8
        $str = iconv('UCS-2LE', 'UTF-8', $str);
        return $str;
    }

    // https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename

    /**
     * @param $filename
     * @return string
     */
    public static function BeautifyFilename($filename)
    {
        // reduce consecutive characters
        $filename = preg_replace(array(
            // "file   name.zip" becomes "file-name.zip"
            '/ +/',
            // "file___name.zip" becomes "file-name.zip"
            '/_+/',
            // "file---name.zip" becomes "file-name.zip"
            '/-+/'
        ), '-', $filename);
        $filename = preg_replace(array(
            // "file--.--.-.--name.zip" becomes "file.name.zip"
            '/-*\.-*/',
            // "file...name..zip" becomes "file.name.zip"
            '/\.{2,}/'
        ), '.', $filename);
        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');
        return $filename;
    }

    /**
     * @param $filename
     * @param bool $beautify
     * @return string
     */
    public static function FilterFilename($filename, $beautify = true)
    {
        // sanitize filename
        $filename = preg_replace(
            '~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
            '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // optional beautification
        if ($beautify) $filename = self::BeautifyFilename($filename);
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
        return $filename;
    }

    /**
     * @param $str
     * @return string
     */
    public static function ExcelTitleOnly($str)
    {
        return self::Truncate(preg_replace('/\s+/si', ' ', preg_replace('/[^a-z0-9\s]/si', ' ', trim($str))), 31, false, false);
    }

    // https://stackoverflow.com/questions/3109978/display-numbers-with-ordinal-suffix-in-php

    /**
     * @param $number
     * @return string
     */
    public static function Ordinal($number)
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }

    /**
     * @param $filename
     * @return array
     */
    public static function CSVToAssociativeArray($filename, $clean_header = false, $has_header = true)
    {
        return self::CSVArrayToAssociativeArray(file($filename), $clean_header, $has_header);
    }

    /**
     * @param $filename
     * @return array
     */
    public static function CSVArrayToAssociativeArray($array, $clean_header = false, $has_header = true)
    {
        if (!is_array($array)) {
            $array = explode("\n", trim($array));
        }

        $rows = array_map('str_getcsv', $array);
        if (!$has_header) {
            return $rows;
        }

        $header = array_shift($rows);
        if ($clean_header) {
            foreach ($header as $i => $item) {
                $item = preg_replace('/[^a-z0-9]/si', ' ', $item);
                $item = preg_replace('/\s+/si', ' ', $item);
                $item = trim($item);
                $item = str_replace(' ', '_', $item);
                $header[$i] = strtolower($item);
            }
        }
        $csv = [];
        foreach ($rows as $row) {
            if (sizeof($header) != sizeof($row)) {
                continue;
            }
            $csv[] = array_combine($header, $row);
        }
        return $csv;
    }

    /**
     * @param $tsv
     * @return array
     */
    public static $_SEPARATOR;

    public static function TSVToArray($tsv, $separator = "\t")
    {
        self::$_SEPARATOR = $separator;
        // https://stackoverflow.com/questions/4801895/csv-to-associative-array
        // https://stackoverflow.com/questions/28690855/str-getcsv-on-a-tab-separated-file
        /* Map Rows and Loop Through Them */
        $rows = array_map(function ($v) {
            return str_getcsv($v, self::$_SEPARATOR);
        }, explode("\n", $tsv));
        $header = array_shift($rows);
        $n = sizeof($header);
        $csv = [];
        foreach ($rows as $row) {
            $m = sizeof($row);
            for ($j = $m; $j < $n; $j++) {
                $row[] = ''; // fill in missing fields with emptry strings
            }
            if (sizeof($row) != $n) {
                continue;
            }
            $csv[] = array_combine($header, $row);
        }
        return $csv;
    }

    /**
     * @param $tsv
     * @return array
     */
    public static function TSVToArrayMap(&$tsv, $mapping_function = null, $filename = null, $class = null, $ignore_errors = false)
    {
        $tsv = trim($tsv); // remove trailing whitespace
        // https://stackoverflow.com/questions/4801895/csv-to-associative-array
        // https://stackoverflow.com/questions/28690855/str-getcsv-on-a-tab-separated-file
        /* Map Rows and Loop Through Them */
        $rows = array_map(function ($v) {
            return str_getcsv($v, "\t");
        }, explode("\n", $tsv));
        $header = array_shift($rows);
        $n = sizeof($header);
        $csv = [];
        foreach ($rows as $row) {
            $m = sizeof($row);
            for ($j = $m; $j < $n; $j++) {
                $row[] = ''; // fill in missing fields with emptry strings
            }
            if (sizeof($row) != $n) {
                if (!$ignore_errors) {
                    Halt([$header, $row]);
                }
            }
            if ($mapping_function) {
                call_user_func($mapping_function, array_combine($header, $row), $filename, $class);
            } else {
                $csv[] = array_combine($header, $row);
            }
        }
        return $csv;
    }

    /**
     * @param $str
     * @return null|string|string[]
     */
    public static function KeyboardOnly($str)
    {
        $str = preg_replace('/[^a-z0-9\!\@\#\$\%\^\&\*\(\)\-\=\_\+\[\]\\\{\}\|\;\'\:\"\,\.\/\<\>\\\?\ \r\n]/si', '', $str);
        return preg_replace('/\s+/si', ' ', $str);
    }

    /**
     * @param $xml
     * @return mixed
     */
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
                    try {
                        if (!is_array($xml_array)) {
                            $xml_array = [];
                        }

                        $php_stmt_att = $php_stmt . '[$x_tag]' . $add . '[$key] = $value;';
                        eval($php_stmt_att);
                    } catch (Exception $ex) {
                        CleanHalt([$xml_array, $ex]);
                    }
                }
            }
        }
        return $xml_array;
    }


    /**
     * @param $string
     * @param $ends_with
     * @return bool
     */
    public static function EndsWith($string, $ends_with, $case_sensitive = true)
    {
        if (!$case_sensitive) {
            return strcasecmp(substr($string, -strlen($ends_with), strlen($ends_with)), $ends_with) == 0;
        }
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
     * @param $remove
     * @param $string
     * @return bool|string
     */
    public static function RemoveFromEnd($remove, $string)
    {
        $remove_length = strlen($remove);

        return substr($string, 0, strlen($string) - $remove_length);
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

    /**
     * @param $value
     * @return mixed
     */
    public static function FormFilter($value)
    {
        return str_replace('"', '\\"', $value);
    }

    /**
     * @param $js
     * @return string
     */
    public static function EchoJS($js)
    {
        return addcslashes(str_replace('"', "'", $js), "'");
    }

    /**
     * @param $data
     * @return string
     */
    public static function ArrayToXML($data)
    {
        $xml = '';
        foreach ($data as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }

        return $xml;
    }

    /**
     * @param $value
     * @param int $brightness
     * @param int $max
     * @param int $min
     * @param string $thirdColorHex
     * @return string
     */
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

    /**
     * @param $string
     * @return string
     */
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

    /**
     * @param $num
     * @return string
     */
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
        if (!is_numeric($val)) {
            return '--';
        }

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
        $desc = utf8_encode($desc);
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

    /**
     * @param $val
     * @return float|string
     */
    public static function Numeric($val)
    {
        // handle scientific notation, force into decimal format
        if (stristr($val, 'E')) {
            $temp = explode('E', $val);
            if (sizeof($temp) == 2) {
                // https://stackoverflow.com/questions/1471674/why-is-php-printing-my-number-in-scientific-notation-when-i-specified-it-as-00
                return rtrim(rtrim(sprintf('%.8F', $temp[0] * pow(10, $temp[1])), '0'), ".");
            }
        }
        // handle basic numbers
        $val = preg_replace('/[^0-9\.-]/si', '', $val);
        if (is_numeric($val)) {
            $res = trim($val * 1.0);
            if ($res) {
                return $res;
            }
        }
        return $val;
    }

    /**
     * @param $val
     * @return float|string
     */
    public static function NumbersOnly($val, $return_orig_on_zero = true)
    {
        $res = trim(preg_replace('/[^0-9]/si', '', $val));
        if (!$res) {
            return $return_orig_on_zero ? $val : 0;
        }
        return $res;
    }

    /**
     * @param $val
     * @return string
     */
    public static function NumericPhone($val)
    {
        $res = trim(preg_replace('/[^0-9]/si', '', $val) * 1.0);
        if (!$res) {
            return $val;
        }
        return $res;
    }

    /**
     * @param $val
     * @return string
     */
    public static function PhoneNumber($val)
    {
        if (preg_match('/^\+?\d?(\d{3})(\d{3})(\d{4})$/', $val, $matches)) {
            $result = $matches[1] . '-' . $matches[2] . '-' . $matches[3];

            return $result;
        }
        return $val;
    }

    /**
     * @param $count
     * @param string $str
     * @return string
     */
    public static function GetPlaceholders($count, $str = '{{}}')
    {
        return implode(',', array_fill(0, $count, $str));
    }

    /**
     * @param $count
     * @return string
     */
    public static function GetSQLServerPlaceholders($count)
    {
        return self::GetPlaceholders($count, '@');
    }

    /**
     * @param $val
     * @return int
     */
    public static function WordCount($val)
    {
        return sizeof(explode(' ', preg_replace('/\s+/si', ' ', $val)));
    }

    /**
     * @param $hex
     * @return string
     */
    public static function Base16to10($hex)
    {
        return base_convert($hex, 16, 10);
    }

    /**
     * @param $md5
     * @return string
     */
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

    /**
     * @param $str
     * @param $length
     * @param bool $words
     * @return string
     */
    public static function Truncate($str, $length, $words = false, $dots = true)
    {
        if (strlen($str) > $length) {
            if ($words) {
                $s = strpos($str, ' ', $length);
                return substr($str, 0, $s) . ($dots ? '...' : '');
            } else {
                return substr($str, 0, $length) . ($dots ? '...' : '');
            }
        }
        return $str;
    }

    /**
     * @param $json
     * @return array|string
     */
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
                            if ($json[$i] instanceof stdClass) {
                                $json[$i] = json_decode(json_encode($json[$i]), true);
                            } else {
                                Halt(['error' => 'fix_json unknown object', $json[$i]]);
                            }
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


    public static function CleanCompanyName($company)
    {
        $company = strtolower($company);
        $company = preg_replace('/\s+/', ' ', $company);
        $company = preg_replace('/[\.,\(\)\*]/', '', $company);
        $company = trim($company);

        $company = explode(' ', $company);
        foreach ($company as $i => $part) {
            if (in_array($part, [
                'n/a',
                'co',
                'corp',
                'corporation',
                'company',
                'llc',
                'of',
                'for',
                'the',
                '&',
                'inc',
                'na',
                //'mgt',
                //'mgmt',
                'llp',
                //'ny',
                'at',
                'ltd',
                'plc',
                'for',
                'in',
                //'dept',
                //'ctr',
                //'cntr',
                //'tech',
                //'assoc',
                //'assn',
                //'cty',
                //'gvmt',
                //'govt',
                'inst',
                'limited',
                'pvt',
                'and',
            ])) {
                unset($company[$i]);
                continue;
            }

            switch ($part) {
                case 'dept':
                    $company[$i] = 'Department';
                    break;
                case 'mgt':
                    $company[$i] = 'Management';
                    break;
                case 'mgmt':
                    $company[$i] = 'Management';
                    break;
                case 'ny':
                    $company[$i] = 'NewYork';
                    break;
                case 'ctr':
                    $company[$i] = 'Center';
                    break;
                case 'cntr':
                    $company[$i] = 'Center';
                    break;
                case 'tech':
                    $company[$i] = 'Technology';
                    break;
                case 'assoc':
                    $company[$i] = 'Association';
                    break;
                case 'assn':
                    $company[$i] = 'Association';
                    break;
                case 'cty':
                    $company[$i] = 'City';
                    break;
                case 'gvmt':
                    $company[$i] = 'Government';
                    break;
                case 'govt':
                    $company[$i] = 'Government';
                    break;
                case 'inst':
                    $company[$i] = 'Institute';
                    break;
            }
        }
        $company = trim(implode(' ', $company));
        return strtolower($company);
    }

    /**
     * @param $text
     *
     * @return mixed
     */
    public static function ToSearchable($text, $replacement = '')
    {
        return preg_replace('/[^a-z0-9]/si', $replacement, strtolower($text));
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
    public static function StringToHTML($text, $convert_urls = false)
    {
        $text = str_replace("\r", '', $text);
        $text = preg_replace('/\n+/si', "\n", $text);

        if ($convert_urls) {
            // https://stackoverflow.com/questions/1960461/convert-plain-text-urls-into-html-hyperlinks-in-php
            $url = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
            $text = preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $text);
        }
        $t = explode("\n", $text);
        return '<p>' . implode("</p><p>", $t) . '</p>';
    }

    /**
     * @param $html
     * @return mixed
     */
    public static function HTMLToString($html)
    {
        $html = trim(strip_tags($html, '<p><br>'));
        $html = str_replace("\r", ' ', $html);
        $html = str_replace("\n", ' ', $html);
        $html = str_ireplace('&nbsp;', ' ', $html);
        $html = preg_replace('/\s+/', ' ', $html);

        $html = str_ireplace('<p>', '', $html);
        $html = str_ireplace('</p>', "\r\n", $html);
        $html = str_ireplace('<br>', "\r\n", $html);
        $html = str_ireplace('<br/>', "\r\n", $html);

        $html = preg_replace('/\ +/', ' ', $html);
        return $html;
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

    /**
     * @param $pattern
     * @param $multiplier
     * @param string $separator
     * @return string
     */
    public static function StringRepeatCS($pattern, $multiplier, $separator = ',')
    {
        $t = [];
        for ($j = 0; $j < $multiplier; $j++) {
            $t[] = $pattern;
        }
        return implode($separator, $t);
    }

    /**
     * @param $array
     * @param string $accessor
     * @param string $function
     * @return string
     */
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

    /**
     * @param $var
     * @param string $default
     * @return string
     */
    public static function ShowOrDefault($var, $default = 'n/a')
    {
        return $var ? htmlspecialchars_decode($var) : $default;
    }

    public static function FontColor($background_color)
    {
        $rgb = Color::HexToRGB($background_color);
        $lumens = $rgb->Brightness();
        if ($lumens >= 130) {
            return '#000';
        }
        return '#fff';
    }

    public static function StrToHex($string)
    { // https://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= ' ' . substr('0' . $hexCode, -2);
        }
        return trim(strtoupper($hex));
    }

    /**
     * @param $str
     *
     * @return string
     */
    public static function CapsToSpaces($str)
    {
        $results = [];
        preg_match_all('/[A-Z\d][^A-Z\d]*/', $str, $results);
        return implode(' ', $results[0]);
    }

    public static function FlattenArray($array, $parents = null, &$dest = null)
    {
        foreach ($array as $k => $v) {

            $k = preg_replace('/[^a-z0-9]/si', '', $k);

            if (!is_array($v)) {
                $dest[$parents . $k] = $v;
                continue;
            }
            self::FlattenArray($v, $parents . $k . '_', $dest);
        }
    }
}

