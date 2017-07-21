<?php
/**
 *
 * @author ben
 * @property string path
 * @property array data
 */
class APIRequest
{
    private $_path = null;
    private $_res = null;
    private $_error = null;
    private $_data = null;
    private $_raw = null;
    private $_headers = null;

    protected $RawData = null;

    public $_http_headers = null;

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

    protected function _Post($path, $data = null, $headers = null)
    {
        $ch = curl_init();

        $host = parse_url($path);
        if (!isset($host['host'])) {
            Debug::Halt([$path, $host]);
        }
        $host = $host['host'];

        $url = $path;

        if (!$headers) {
            $header[] = "Accept: */*";
            $header[] = "Accept-Language: en-us";
            $header[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";
        } else {
            $header = $headers;
        }
        $header[] = "Host: $host";
        $this->headers = $header;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_setopt($ch, CURLOPT_POST, true);
        if ($this->RawData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->RawData);
            if($data) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
            }
        } else {
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        // grab URL and pass it to the browser
        $retr = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($retr, 0, $header_size);
        $body = substr($retr, $header_size);

        $this->_http_headers = $header;

        return $body;
    }

    protected function _Get($path, $data = null, $headers = null)
    {
        $ch = curl_init();

        $host = parse_url($path);
        if (!isset($host['host'])) {
            Debug::Halt([$path, $host]);
        }
        $host = $host['host'];

        $url = $path;

        if (!$headers) {
            $header[] = "Accept: */*";
            $header[] = "Accept-Language: en-us";
            $header[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)";
        } else {
            $header = $headers;
        }
        $header[] = "Host: $host";

        $this->headers = $header;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_POST, false);
        if ($data) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        // grab URL and pass it to the browser
        $retr = curl_exec($ch);

        return $retr;
    }

    protected function _Log()
    {
        global $Session;
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

        $this->_Log();
    }

    public function Get($headers = null)
    {
        $res = $this->_Get($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log();
    }
}