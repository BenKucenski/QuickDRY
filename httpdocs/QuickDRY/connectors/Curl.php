<?php

class Curl
{
    public $Body;
    public $HeaderHash;
    public $Header;

    public static function json_err_str($err_code)
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
            default:
                return ' - Unknown error';
        }
    }

    /**
     * @param $params
     *
     * @return string
     */
    public static function params_to_string($params)
    {
        $res = [];
        foreach($params as $k=>$v)
            $res[] = urlencode($k) .'=' . urlencode($v);

        return implode('&',$res);
    }

    /**
     * @param        $path
     * @param        $params
     * @param bool   $get
     * @param string $host
     *
     * @return Curl
     */
    public static function Post($path, $params)
    {
        if(is_array($params)) {
            $params = self::params_to_string($params);
        }
        // Initiate CURL
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST,        true);

        $parts = parse_url($path);
        $host = isset($parts['host']) ? $parts['host'] : '';

        $header[] = "Accept: */*";
        $header[] = "Accept-Language: en-us";
        $header[] = "UA-CPU: x86";
        $header[] = "Host: $host";
        $header[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";
        $header[] = "Connection: Keep-Alive";

        if(!defined('COOKIE_FILE')) {
            define('COOKIE_FILE', './cookie.txt');
        }
        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


        // Pass our values
        curl_setopt($ch, CURLOPT_URL, $path);

        if($params != "") curl_setopt($ch, CURLOPT_POSTFIELDS,     $params);


        $content = curl_exec($ch);

        $err = curl_error($ch);
        if($err) {
            Halt($err);
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($content, 0, $header_size);
        $body = substr($content, $header_size);
        curl_close($ch);


        $head = explode("\n",$header);
        $head_hash = [];
        foreach($head as $val)
        {
            $val = explode(": ",$val);
            if(isset($val[1]))
                $head_hash[$val[0]]=$val[1];
        }

        $res = new Curl();
        $res->Body = $body;
        $res->Header = $header;
        $res->HeaderHash = $head_hash;
        return $res;
    }

    /**
     * @param        $path
     * @param        $params
     * @param bool   $get
     * @param string $host
     *
     * @return Curl
     */
    public static function Get($path, $params = null, $username = null, $password = null)
    {
        if(is_array($params)) {
            $params = self::params_to_string($params);
        }
        // Initiate CURL
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST,        false);

        if($username && $password) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        }
        $parts = parse_url($path);
        $host = isset($parts['host']) ? $parts['host'] : '';

        $header[] = "Accept: */*";
        $header[] = "Accept-Language: en-us";
        $header[] = "UA-CPU: x86";
        $header[] = "Host: $host";
        $header[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";
        $header[] = "Connection: Keep-Alive";

        if(!defined('COOKIE_FILE')) {
            define('COOKIE_FILE', './cookie.txt');
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


        // Pass our values
        if($params) {
            curl_setopt($ch, CURLOPT_URL, $path . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $path);
        }

        $content = curl_exec($ch);

        $err = curl_error($ch);
        if($err) {
            Halt($err);
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($content, 0, $header_size);
        $body = substr($content, $header_size);
        curl_close($ch);


        $head = explode("\n",$header);
        $head_hash = [];
        foreach($head as $val)
        {
            $val = explode(": ",$val);
            if(isset($val[1]))
                $head_hash[$val[0]]=$val[1];
        }

        $res = new Curl();
        $res->Body = $body;
        $res->Header = $header;
        $res->HeaderHash = $head_hash;
        return $res;
    }
}



