<?php
/**
 *
 * @author ben
 * @property string path
 * @property array data
 * @property stdClass res
 */
class APIRequest
{
    private $_path = null;
    private $_method = null;
    private $_res = null;
    private $_error = null;
    private $_data = null;
    private $_raw = null;
    private $_headers = null;

    public static $UseLog = false;
    public static $CacheTimeoutSeconds;

    public function __get($name)
    {
        switch ($name) {
            case 'headers':
                return $this->_headers;

            case 'raw':
                return $this->_raw;

            case 'data':
                return $this->_data;

            case 'path':
                return $this->_path;

            case 'res':
                return $this->_res;

            case 'error':
                return $this->_error;

            default:
                return isset($this->_res->$name) ? $this->_res->$name : null;
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'headers':
                $this->_headers = $value;
                break;

            case 'path':
                $this->_path = $value;
                break;

            case 'res':
                $this->_res = $value;
                break;

            case 'error':
                $this->_error = $value;
                break;

            default:
                $this->_data[$name] = $value;
        }
    }

    private function _Request($path, $data = null, $headers = null, $post = true)
    {
        if(self::$CacheTimeoutSeconds > -1) {
            $hash = md5(serialize([$path, $data, $headers, $post]));
            $dir = 'logs/cache';
            if(!is_dir($dir)) {
                mkdir($dir);
            }
            $file = $dir .'/' .$hash .'.txt';
            if(file_exists($file)) {
                if(time() - filectime($file) < self::$CacheTimeoutSeconds) {
                    $fp = fopen($file, 'r');
                    $retr = fread($fp, filesize($file));
                    fclose($fp);
                    return $retr;
                }
            }
        }

        $ch = curl_init();

        $host = parse_url($path);
        if (!isset($host['host'])) {
            return null;
        }
        $host = $host['host'];

        $url = $path;

        if (is_null($headers)) {
            $headers = [];
        }
        $headers[] = "Host: $host";
        $headers[] = "Accept: */*";
        $headers[] = "Accept-Language: en-us";
        $headers[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";

        $this->headers = $headers;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_POST, $post);
        if ($data && $post) {
            if(isset($data['json'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data['json']));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        // grab URL and pass it to the browser
        $retr = curl_exec($ch);

        $this->_error = curl_error($ch);

        if(self::$CacheTimeoutSeconds > -1) {
            $fp = fopen($file, 'w');
            fwrite($fp, $retr);
            fclose($fp);
        }

        return $retr;
    }

    protected function _Post($path, $data = null, $headers = null)
    {
        return $this->_Request($path, $data, $headers, true);
    }

    protected function _Get($path, $data = null, $headers = null)
    {
        return $this->_Request($path, $data, $headers, false);
    }

    protected function _Log($Method = 'Get')
    {
        $this->_method = $Method;

        if(!self::$UseLog) {
            return;
        }
        global $Session;
        if(!$Session) {
            return;
        }
        $a = $Session->api_log;
        $a[] = $this;
        $Session->api_log = $a;
    }

    public function GetProps()
    {
        if (!is_null($this->res)) {
            if (is_object($this->res)) {
                $props = get_object_vars($this->res);
            } else {
                $props = $this->res;
            }
        } else {
            $props = array();
        }

        return $props;
    }

    public function Post($headers = null)
    {
        $res = $this->_Post($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Post');
    }

    public function Get($headers = null)
    {
        $res = $this->_Get($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Get');
    }
}