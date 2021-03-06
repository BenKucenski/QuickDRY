<?php

/**
 * Class HTTP
 */
class HTTP extends SafeClass
{
    /**
     * @param $url
     * @return mixed
     */
    public static function CheckURL($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $retcode;
    }

    /**
     * @param $query_str
     * @param $params
     * @return string
     */
    public static function RemoveParameters($query_str, $params)
    {
        parse_str($query_str, $get);
        foreach ($params as $param) {
            if (!isset($get[$param])) {
                continue;
            }
            unset($get[$param]);
        }
        return http_build_query($get);
    }

    /**
     * @param JsonStatusResult $result
     */
    public static function ExitJSONResult(JsonStatusResult &$result)
    {
        $res = $result->ToArray(true);
        $json = json_decode(json_encode($res), true);

        self::ExitJSON($json, $result->status);
    }

    /**
     * @return bool
     */
    public static function IsSecure()
    {
        if (defined('HTTP_HOST_IS_SECURE') && HTTP_HOST_IS_SECURE) { // needed for sites running behind a proxy
            return true;
        }

        if (!isset($_SERVER['HTTPS'])) {
            return false;
        }

        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * @param $array
     * @param $name
     * @return string
     */
    public static function ArrayToHTTPQuery($array, $name)
    {
        $res = [];
        foreach ($array as $v) {
            $res[] = $name . '[]=' . urlencode($v);
        }
        return implode('&', $res);
    }


    /**
     * @param $err
     */
    public static function Redirect($url = null)
    {
        if (is_null($url)) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                header('location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header('location: /');
            }
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     * @param $err
     */
    public static function RedirectError($err, $url = '/')
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            Log::Insert($err, true);
            return;
        }

        $_SESSION['error'] = serialize(str_replace('"', '\\"', str_replace('\\', '\\\\', $err))); // make it compatible with the Session object

        if ($url == '/' && isset($_SERVER['HTTP_REFERER'])) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     * @param $notice
     * @param string $url
     */
    public static function RedirectNotice($notice, $url = '/')
    {
        $_SESSION['notice'] = serialize(str_replace('"', '\\"', str_replace('\\', '\\\\', $notice))); // make it compatible with the Session object

        if ($url === '/' && isset($_SERVER['HTTP_REFERER'])) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     *
     */
    public static function ReloadPage()
    {
        header('location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    /**
     * @param $url
     * @param $title
     */
    public static function ExitJavascript($url, $title)
    {
        echo 'Redirecting to <a id="redirect_url" href="' . $url . '">' . $title . '</a><script>
    (function() {
        window.location = document.getElementById("redirect_url");
    })();
    </script>
    ';
        exit;
    }

    /**
     * @param $json
     * @param int $HTTP_STATUS
     */
    public static function ExitJSON($json, $HTTP_STATUS = HTTP_STATUS_OK)
    {
        if ($HTTP_STATUS) {
            header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
        }
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT');
        header(
            'Access-Control-Allow-Headers: X-User-Email, X-User-Token, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers'
        );
        if (!is_array($json)) { // used by api/v2/sheets/json.get.php to indicate the data is already json encoded
            exit($json);
        }
        exit(json_encode(Strings::FixJSON($json), JSON_PRETTY_PRINT));
    }

    /**
     * @param $json
     * @param int $HTTP_STATUS
     */
    public static function ExitFile($content, $filename, $HTTP_STATUS = HTTP_STATUS_OK, $ContentType = null, $Download = true)
    {
        if ($HTTP_STATUS) {
            header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
        }
        if ($Download) {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT');
            header(
                'Access-Control-Allow-Headers: X-User-Email, X-User-Token, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers'
            );
        } else {
            header('Content-Type: ' . $ContentType);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT');
            header(
                'Access-Control-Allow-Headers: X-User-Email, X-User-Token, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers'
            );

        }
        exit($content);
    }

    /**
     * @param $header
     */
    public static function AltHeader($header)
    {
        if (!defined('NO_HEADERS'))
            header($header);
    }

    /**
     * @param $serialized
     *
     * @return array
     */
    public static function PostFromSerialized($serialized)
    {
        $reqs = explode('&', $serialized);
        $post = [];
        foreach ($reqs as $req) {
            $nk = explode('=', $req);
            $nk[0] = urldecode($nk[0]);
            if (substr($nk[0], -2) === '[]') {
                $nk[0] = substr($nk[0], 0, strlen($nk[0]) - 2);
                $post[$nk[0]][] = urldecode($nk[1]);
            } else
                $post[$nk[0]] = isset($nk[1]) ? urldecode($nk[1]) : '';
        }
        return $post;
    }
}

