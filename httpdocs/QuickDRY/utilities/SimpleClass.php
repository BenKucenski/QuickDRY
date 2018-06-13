<?php

/**
 * Class SimpleClass
 */
class SimpleClass
{
    private $_vars = [];

    /**
     * @return array
     */

    public function ToArray()
    {
        return $this->_vars;
    }

    /**
     * @param array $row
     */
    public function FromRow($row) {
        foreach($row as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $value = !is_array($value) ? trim($value) : $value;
        $this->_vars[$name] = $value;
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
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
        return isset($this->_vars[$name]);
    }
}