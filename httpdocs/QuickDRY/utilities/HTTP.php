<?php
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

function ReloadPage()
{
    header('location: ' . $_SERVER['REQUEST_URI']);
    exit;
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

function exit_json($json, $HTTP_STATUS = HTTP_STATUS_OK)
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
    exit(json_encode(fix_json($json), JSON_PRETTY_PRINT));
}

/**
 * @param $header
 */
function AltHeader($header) {
    if(!defined('NO_HEADERS'))
        header($header);
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
