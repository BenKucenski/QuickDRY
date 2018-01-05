<?php
class HTTP extends SafeClass
{
    /**
     * @param $err
     */
    public static function RedirectError($err, $url = '/')
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

    public static function RedirectNotice($notice, $url = '/')
    {
        global $Session;
        $Session->notice = $notice;
        if(isset($_SERVER['HTTP_REFERER']))
            header('location: ' . $_SERVER['HTTP_REFERER']);
        else {
            header('location: ' . $url);
        }
        exit();
    }

    public static function ReloadPage()
    {
        header('location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    public static function ExitJavascript($url, $title) {
        echo 'Redirecting to <a id="redirect_url" href="' . $url . '">' . $title . '</a><script>
    (function() {
        window.location = document.getElementById("redirect_url");
    })();
    </script>
    ';
        exit;
    }

    public static function ExitJSON($json, $HTTP_STATUS = HTTP_STATUS_OK)
    {
        if($HTTP_STATUS) {
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
     * @param $header
     */
    public static function AltHeader($header) {
        if(!defined('NO_HEADERS'))
            header($header);
    }

    /**
     * @param $serialized
     *
     * @return array
     */
    public static function PostFromSerialized($serialized)
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
}


function exit_json($json, $HTTP_STATUS = HTTP_STATUS_OK)
{
    return HTTP::ExitJSON($json, $HTTP_STATUS);
}


function PostFromSerialized($serialized)
{
    return HTTP::PostFromSerialized($serialized);
}
