<?php

class Elastic_Base extends SafeClass
{
    protected $_id = null;
    protected static $_index = null;
    protected static $_type = null;
    protected static $_strong_type = null;

    public function __get($name)
    {
        switch ($name) {
            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            default:
                return parent::__set($name, $value);
        }
    }

    private static function _strong_type($name, $value)
    {
        if ($value instanceof DateTime) {
            return SolrTime($value);
        }

        if (isset(static::$_strong_type[$name])) {
            switch (static::$_strong_type[$name]) {
                case 'string':
                    if (is_array($value)) {
                        $value = json_encode(fix_json($value));
                    } else {
                        $value = preg_replace('/\s+/si', ' ', $value);
                    }
                    break;
                case 'date':
                    if ($value) {
                        $value = SolrTime($value);
                    } else {
                        $value = null;
                    }
                    break;
                case 'integer':
                case 'long':
                    $value = (int)($value * 1.0);
                    break;
                case 'float':
                case 'double':
                    $value *= 1.0;
                    break;
                case 'geo_point': // double, double
                    break;
                default:
                    Halt($name . ': unknown type "' . static::$_strong_type[$name] . '" for value "' . $value . '"');
            }
        }

        return $value;
    }


    public function FromElastic($_source)
    {
        $missing = [];
        foreach ($_source as $key => $val) {
            if (property_exists(get_class($this), $key)) {
                $this->$key = $val;
            } else {
                if ($key !== 'safe') {
                    $missing[] = 'public $' . $key . ';';
                }
            }
        }


        if (sizeof($missing)) {
            Halt('Calling missing property: ' . implode("\r\n", $missing));
        }
    }


    public function ToArray($ignore_empty = false, $exclude = [])
    {
        $res = get_object_vars($this);
        foreach ($res as $key => $val) {
            if ($ignore_empty) {
                if (!$val) {
                    unset($res[$key]);
                }
            }
            if ($key[0] == '_') {
                unset($res[$key]);
            }
            if (isset(static::$_strong_type[$key])) {
                if (static::$_strong_type[$key] === 'date' && !$val) {
                    unset($res[$key]);
                }
            }
            if (isset($res[$key])) {
                if (is_object($res[$key])) {

                    if ($res[$key] instanceof DateTime) {
                        $res[$key] = $res[$key]->getTimestamp();
                    } else {

                        $res[$key] = $res[$key]->ToArray($ignore_empty, $exclude);
                    }
                } else {
                    if (is_array($res[$key])) {
                        foreach ($res[$key] as $a => $b) {
                            $res[$key][$a] = is_object($b) ? $b->ToArray($ignore_empty, $exclude) : $b;
                        }
                    } else {
                        $res[$key] = static::_strong_type($key, $res[$key]);
                    }
                }
            }
        }

        return $res;
    }
}