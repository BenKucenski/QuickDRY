<?php

/**
 * Class Session
 */
class Server
{
    private $_VALS = [];

    public function __construct($vals = null)
    {
        $this->_VALS = $vals;
    }

    public function GetVals($prefix = '')
    {
        $res = [];
        foreach ($this->_VALS as $k => $v) {
            if (!$prefix || substr($k, 0, strlen($prefix)) === $prefix) {
                $res[$k] = $this->$k;
            }
        }
        foreach ($_SERVER as $k => $v) {
            if (!$prefix || substr($k, 0, strlen($prefix)) === $prefix) {
                $res[$k] = $this->$k;
            }
        }

        return $res;
    }

    /**
     * @param $name
     *
     * @return mixed|string
     */
    public function __get($name)
    {
        if (isset($_SERVER[$name]) && $_SERVER[$name]) {
            return $_SERVER[$name];
        }

        if (isset($this->_VALS[$name])) {
            return unserialize($this->_VALS[$name]);
        }

        return '';
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        if (isset($_SERVER[$name])) {
            unset($_SERVER[$name]);
        }
        if (isset($this->_VALS[$name])) {
            unset($this->_VALS[$name]);
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->_VALS[$name] = serialize($value);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($_SERVER[$name]) || isset($this->_VALS[$name]);
    }
}

$Server = new Server();
