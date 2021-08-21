<?php
namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Debug;

/**
 * Class Curl
 * @property CurlHeader Header
 * @property string Body
 * @property string[] HeaderHash
 * @property string HeaderRaw
 * @property int StatusCode
 */
class Curl
{
    public string $Body;
    public array $HeaderHash;
    public string $HeaderRaw;
    public CurlHeader $Header;
    public int $StatusCode;

    public ?string $URL = null;
    public ?string $Params = null;
    public ?array $SentHeader = null;

    public static bool $FollowLocation = true;

    /**
     * Returns the size of a file without downloading it, or -1 if the file
     * size could not be determined.
     *
     * @param $url - The location of the remote file to download. Cannot
     * be null or empty.
     *
     * @return int The size of the file referenced by $url, or -1 if the size
     * could not be determined.
     */
    public static function GetFileSize( $url )
    { // https://stackoverflow.com/questions/2602612/remote-file-size-without-downloading-file
        // Assume failure.
        $result = -1;

        $curl = curl_init($url);

        // Issue a HEAD request and follow any redirects.
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($curl, CURLOPT_USERAGENT, get_user_agent_string());

        $data = curl_exec($curl);
        curl_close($curl);

        if ($data) {
            $content_length = "unknown";
            $status = "unknown";

            if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
                $status = (int)$matches[1];
            }

            if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
                $content_length = (int)$matches[1];
            }

            // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
            if ($status == 200 || ($status > 300 && $status <= 308)) {
                $result = $content_length;
            }
        }

        return $result;
    }


  /**
   * @param $path
   * @param $params
   * @param bool $tweak_params
   * @param null $additional_headers
   * @return Curl
   */
    public static function Post($path, $params, bool $tweak_params = false, $additional_headers = null): Curl
    {
        if (is_array($params)) {
            $params = http_build_query($params);
            if($tweak_params) {
                // USPS sends XML as a parameter and it needs a few characters "fixed"
                $params = str_replace('+', '%20', $params);
                $params = str_replace('%3D', '=', $params);
                $params = str_replace('%2F', '/', $params);
            }
        }
        // Initiate CURL
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, true);

        $parts = parse_url($path);
        $host = $parts['host'] ?? '';

        $header[] = "Accept: */*";
        $header[] = "Accept-Language: en-us";
        $header[] = "UA-CPU: x86";
        $header[] = "Host: $host";
        $header[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";
        $header[] = "Connection: Keep-Alive";

        if($additional_headers) {
            foreach($additional_headers as $k => $v) {
                $header[] = $k . ': ' . $v;
            }
        }

        if (!defined('COOKIE_FILE')) {
            define('COOKIE_FILE', './cookie.txt');
        }
        // Pass our values
        curl_setopt($ch, CURLOPT_URL, $path);

        if ($params != "") {
            $header[] = 'Content-Length' . ': ' . strlen($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, self::$FollowLocation);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($ch);

        $err = curl_error($ch);
        if ($err) {
            Debug::Halt($err);
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($content, 0, $header_size);
        $body = substr($content, $header_size);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);


        $head = explode("\n", $response_header);
        $head_hash = [];
        foreach ($head as $val) {
            $val = explode(": ", $val);
            if (isset($val[1])) {
                $head_hash[$val[0]] = trim($val[1]);
            }
        }

        $res = new Curl();
        $res->Body = $body;
        $res->HeaderRaw = $response_header;
        $res->Header = new CurlHeader($head_hash);
        $res->HeaderHash = $head_hash;
        $res->StatusCode = $status;

        $res->URL = $path;
        $res->Params = $params;
        $res->SentHeader = $header;

        return $res;
    }

  /**
   * @param $path
   * @param null $params
   * @param null $username
   * @param null $password
   * @param null $additional_headers
   * @return Curl
   */
    public static function Get($path, $params = null, $username = null, $password = null, $additional_headers = null): Curl
    {
        if (is_array($params)) {
            $params = http_build_query($params);
        }
        // Initiate CURL
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, false);

        if ($username && $password) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        }
        $parts = parse_url($path);
        $host = $parts['host'] ?? '';

        $header[] = "Accept: */*";
        $header[] = "Accept-Language: en-us";
        $header[] = "UA-CPU: x86";
        $header[] = "Host: $host";
        $header[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";
        $header[] = "Connection: Keep-Alive";

        if($additional_headers) {
            foreach($additional_headers as $k => $v) {
                $header[] = $k . ': ' . $v;
            }
        }

        if (!defined('COOKIE_FILE')) {
            define('COOKIE_FILE', './cookie.txt');
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, self::$FollowLocation);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


        // Pass our values
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $path . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $path);
        }

        $content = curl_exec($ch);

        $err = curl_error($ch);
        if ($err) {
          Debug::Halt($err);
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header = substr($content, 0, $header_size);
        $body = substr($content, $header_size);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $head = explode("\n", $response_header);
        $head_hash = [];
        foreach ($head as $val) {
            $val = explode(": ", $val);
            if (isset($val[1])) {
                $head_hash[$val[0]] = trim($val[1]);
            }
        }

        $res = new Curl();
        $res->Body = $body;
        $res->HeaderRaw = $response_header;
        $res->Header = new CurlHeader($head_hash);
        $res->HeaderHash = $head_hash;
        $res->StatusCode = $status;

        $res->URL = $path;
        $res->Params = $params;
        $res->SentHeader = $header;

        return $res;
    }

    /**
     * @param $url
     * @return bool|string
     */
    public static function URLGetContents($url)
    {
        // this turns off the SSL error checking for grabbing files from trusted sources
        // https://stackoverflow.com/questions/26148701/file-get-contents-ssl-operation-failed-with-code-1-and-more
        $context = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

      return file_get_contents($url, false, stream_context_create($context));
    }
}



