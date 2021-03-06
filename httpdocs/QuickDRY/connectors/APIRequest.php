<?php

/**
 *
 * @author ben
 * @property string path
 * @property string raw
 * @property string error
 * @property array data
 * @property string raw_post_data
 * @property stdClass[] res
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
    private $_cache_file = null;
    private $_return_headers = null;
    private $_curl_info = null;

    public static $UseLog = false;
    public static $CacheTimeoutSeconds;
    public static $ShowURL;

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        switch ($name) {
            case 'curl_info':
                return $this->_curl_info;

            case 'return_headers':
                return $this->_return_headers;

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

            case 'cache_file':
                return $this->_cache_file;

            case 'error':
                return $this->_error;

            default:
                return isset($this->_res->$name) ? $this->_res->$name : null;
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'curl_info':
                $this->_curl_info = $value;
                break;

            case 'headers':
                $this->_headers = $value;
                break;

            case 'return_headers':
                $this->_return_headers = $value;
                break;

            case 'path':
                $this->_path = $value;
                break;

            case 'res':
                $this->_res = $value;
                break;

            case 'cache_file':
                $this->_cache_file = $value;
                break;

            case 'raw':
                $this->_raw = $value;
                break;

            case 'error':
                $this->_error = $value;
                break;

            default:
                $this->_data[$name] = $value;
        }
    }

    public function ClearCache()
    {
        $file = $this->_cache_file;

        if ($file && file_exists($file) && filesize($file)) {
            rename($file, 'last_attempt.txt');
            //unlink($file);
        }

    }

    /**
     * @param $path
     * @param null $data
     * @param null $headers
     * @param bool $post
     * @return bool|mixed|string
     */
    private function _Request($path, $data = null, $headers = null, $post = true, $custom_method = null)
    {
        if (self::$CacheTimeoutSeconds) {
            $hash = md5(serialize([$path, $data, $headers, $post]));
            $dir = DOC_ROOT_PATH . '/logs/cache';
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            $dir .= '/' . $hash[0];
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            $dir .= '/' . $hash[1];
            if (!is_dir($dir)) {
                mkdir($dir);
            }

            $file = $dir . '/' . $hash . '.txt';
            $this->_cache_file = $file;

            if (file_exists($file) && filesize($file)) {
                if (time() - filectime($file) < self::$CacheTimeoutSeconds) {
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

        if ($data) {
            if ($post) {
                if (isset($data['raw_post_data'])) {
                    $headers[] = "Content-length: " . strlen($data['raw_post_data']);
                } else {
                    if (isset($data['json'])) {
                        $headers[] = "Content-length: " . strlen(json_encode($data['json']));
                    } else {
                        $headers[] = "Content-length: " . strlen(http_build_query($data));
                    }
                }
            } else {
                $headers[] = "Content-length: 0";
            }
        } else {
            $headers[] = "Content-length: 0";
        }

        $headers[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";

        $this->headers = $headers;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if($custom_method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_method);
        }

        curl_setopt($ch, CURLOPT_POST, $post);
        if ($data) {
            if ($post) {
                if (isset($data['raw_post_data'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data['raw_post_data']);
                } else {
                    if (isset($data['json'])) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data['json']));
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    }
                }
            } else {
                $url .= (stristr($url, '?') === false ? '?' : '&') . http_build_query($data);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        if (self::$ShowURL) {
            Log::Insert($url, true);
        }

        // grab URL and pass it to the browser
        $content = curl_exec($ch);

        $this->_error = curl_error($ch);
        $this->_curl_info = curl_getinfo($ch);


        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($content, 0, $header_size);
        $body = substr($content, $header_size);
        curl_close($ch);


        $head = explode("\n", $header);
        $this->_return_headers = [];
        foreach ($head as $val) {
            $val = explode(": ", $val);
            if (isset($val[1])) {
                $this->_return_headers[$val[0]] = $val[1];
            }
        }


        if (self::$CacheTimeoutSeconds) {

            if (file_exists($file)) {
                unlink($file); // delete the old file so the created time updates
            }

            $fp = fopen($file, 'w');
            fwrite($fp, $body);
            fclose($fp);
        }

        return $body;
    }

    /**
     * @param $path
     * @param null $data
     * @param null $headers
     * @return bool|mixed|string
     */
    protected function _Put($path, $data = null, $headers = null)
    {
        return $this->_Request($path, $data, $headers, true, 'PUT');
    }

    /**
     * @param $path
     * @param null $data
     * @param null $headers
     * @return bool|mixed|string
     */
    protected function _Delete($path, $data = null, $headers = null)
    {
        return $this->_Request($path, $data, $headers, true, 'DELETE');
    }

    /**
     * @param $path
     * @param null $data
     * @param null $headers
     * @return bool|mixed|string
     */
    protected function _Post($path, $data = null, $headers = null)
    {
        return $this->_Request($path, $data, $headers, true);
    }

    /**
     * @param $path
     * @param null $data
     * @param null $headers
     * @return bool|mixed|string
     */
    protected function _Get($path, $data = null, $headers = null)
    {
        return $this->_Request($path, $data, $headers, false);
    }

    /**
     * @param string $Method
     */
    protected function _Log($Method = 'Get')
    {
        global $Web;

        $this->_method = $Method;

        if (!self::$UseLog) {
            return;
        }

        if (!$Web) {
            $a = unserialize($_SESSION['api_log']);
            $a[] = $this;
            $_SESSION['api_log'] = serialize($a);
            return;
        }

        if (!$Web->Session) {
            return;
        }
        $a = $Web->Session->api_log;
        $a[] = $this;
        $Web->Session->api_log = $a;
    }

    /**
     * @return array|stdClass
     */
    public function GetProps()
    {
        if (!is_null($this->res)) {
            if (is_object($this->res)) {
                $props = get_object_vars($this->res);
            } else {
                $props = $this->res;
            }
        } else {
            $props = [];
        }

        return $props;
    }

    /**
     * @param null $headers
     */
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

    /**
     * @param null $headers
     */
    public function Put($headers = null)
    {
        $res = $this->_Put($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Put');
    }

    /**
     * @param null $headers
     */
    public function Delete($headers = null)
    {
        $res = $this->_Delete($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Delete');
    }

    /**
     * @param null $headers
     */
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