<?php



/**
 * Class Request
 */
class Request
{

    private $_vars = [];

    /**
     * @return array
     */

    public function GetVals()
    {
        $vals = [];
        foreach ($_POST as $k => $v) {
            $vals[$k] = $v;
        }
        foreach ($_GET as $k => $v) {
            $vals[$k] = $v;
        }

        return $vals;
    }

    public function __set($name, $value)
    {
        $value = !is_array($value) ? trim($value) : $value;
        $_POST[$name] = $value;
        $_GET[$name] = $value;
        $this->_vars[$name] = $value;
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
        if (isset($_POST[$name])) {
            return is_array($_POST[$name]) ? $_POST[$name] : trim($_POST[$name]);
        }

        if (isset($_GET[$name])) {
            return is_array($_GET[$name]) ? $_GET[$name] : trim($_GET[$name]);
        }

        if (isset($this->_vars[$name])) {
            return is_array($this->_vars[$name]) ? $this->_vars[$name] : trim($this->_vars[$name]);
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($_GET[$name]) || isset($_POST[$name]) || isset($this->_vars[$name]);
    }
}

$Request = new Request();