<?php

namespace QuickDRY\Utilities;

use DateTime;
use Exception;
use ReflectionProperty;
use stdClass;

/**
 * Class SafeClass
 */
class SafeClass
{
    private bool $_HaltOnError = true;
    private array $_MissingProperties = [];
    private array $_Aliases = [];

    public function GetAliases(): array
    {
        return $this->_Aliases;
    }

    /**
     * @return bool
     */
    public function HasMissingProperties(): bool
    {
        return sizeof($this->_MissingProperties) > 0;
    }

    /**
     * @return string
     */
    public function GetMissingProperties(): string
    {
        return implode("\n", $this->_MissingProperties);
    }

    /**
     * @param bool $true_or_false
     */
    public function HaltOnError(bool $true_or_false)
    {
        $this->_HaltOnError = $true_or_false;
    }

    /**
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
        if ($this->_HaltOnError) {
            Halt('QuickDRY Error: public $' . $name . '; is not a property of ' . get_class($this));
        } else {
            $this->_MissingProperties[$name] = 'public ?string $' . $name . ' = null;';
        }
        return null;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        if ($this->_HaltOnError) {
            Halt('QuickDRY Error: public $' . $name . '; is not a property of ' . get_class($this));
        } else {
            $this->_MissingProperties[$name] = 'public ?string $' . $name . ' = null;';
        }
        return $value;
    }

    /**
     * @param bool $ignore_empty
     * @param array|null $exclude
     * @return array
     */
    public function ToArray(bool $ignore_empty = false, array $exclude = null): array
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
        }

        foreach ($res as $key => $val) {
            if ($val instanceof DateTime) {
                $res[$key] = Dates::Timestamp($val);
            }
        }
        return $res;
    }

    /**
     * @param array $row
     * @param bool $convert_objects
     */
    public function FromRow(array $row, bool $convert_objects = false)
    {
        $halt_on_error = $this->_HaltOnError;

        $this->HaltOnError(false);
        if (!is_array($row)) {
            Halt($row);
        }
        foreach ($row as $k => $v) {
            if ($convert_objects && is_object($v)) {
                $v = Dates::Timestamp($v);
            }

            $a = preg_replace('/[^a-z0-9_]/si', '', $k);
            if ($a != $k) {
                $this->_Aliases['_' . $a] = $k;
                $k = '_' . $a;
            }

            try {
                $rp = new ReflectionProperty($this, $k);
                $type = $rp->getType()->getName();
                switch ($type) {
                    case 'DateTime':
                        try {
                            $this->$k = new DateTime(Dates::Timestamp($v));
                        } catch (Exception $ex) {
                            $this->$k = null;
                        }
                        break;
                    case 'string':
                        $this->$k = is_array($v) || is_object($v) ? Strings::FixJSON($v) : $v;
                        break;
                    case 'int':
                        $this->$k = is_array($v) || is_object($v) ? (int)Strings::FixJSON($v) : (int)$v;
                        break;
                    case 'float':
                        $this->$k = is_array($v) || is_object($v) ? (float)Strings::FixJSON($v) : (float)$v;
                        break;
                    case 'bool':
                        $this->$k = is_array($v) || is_object($v) ? (bool)Strings::FixJSON($v) : (bool)$v;
                        break;

                    default:
                        $this->$k = $v;
                }

                if ($this->HasMissingProperties()) {
                    if ($halt_on_error) {
                        Halt($this->GetMissingProperties());
                    }
                }
                $this->HaltOnError($halt_on_error);

            } catch (Exception $e) {
                Debug::Halt($e);
            }
        }
    }

    /**
     * @param StdClass[] $array
     * @param $filename
     *
     * pass in an array of SafeClass objects and the file name
     */
    public static function ToCSV(array $array, $filename, $headers = null)
    {
        if (!is_array($array) || !sizeof($array)) {
            Halt('QuickDRY Error: Not an array or empty');
        }

        $header = $headers ?: array_keys(get_object_vars($array[0]));

        $output = fopen("php://output", 'w') or die("Can't open php://output");
        header("Content-Type:application/csv");
        header("Content-Disposition:attachment;filename=\"" . $filename . "\"");
        fputcsv($output, $header);
        foreach ($array as $item) {
            /* @var $item SafeClass */
            $ar = array_values(get_object_vars($item));
            fputcsv($output, $ar);
        }
        fclose($output) or die("Can't close php://output");
        exit;
    }

    /**
     * @param SafeClass[] $items
     * @return SimpleExcel
     */
    public static function ToExcel(array $items): ?SimpleExcel
    {
        if (!sizeof($items)) {
            return null;
        }
        $class = get_called_class();
        $cols = array_keys(get_object_vars($items[0]));
        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = $class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(null, $col);
        }
        return $se;
    }

    /**
     * @param SafeClass[] $items
     * @param string $class
     * @param string $style
     * @param bool $numbered
     * @param int $limit
     * @return string
     */
    public static function ToHTML(
        array  $items,
        string $class = '',
        string $style = '',
        bool   $numbered = false,
        int    $limit = 0
    ): string
    {
        if (!sizeof($items)) {
            return '';
        }

        $se = self::ToExcel($items);

        $html = '<table class="' . $class . '" style="' . $style . '"><thead><tr>';
        if ($numbered) {
            $html .= '<th></th>';
        }
        foreach ($se->Columns as $col => $settings) {
            $html .= '<th>' . $col . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($se->Report as $i => $item) {
            if ($limit && $i >= $limit) {
                break;
            }
            $html .= '<tr>';
            if ($numbered) {
                $html .= '<td>' . ($i + 1) . '</td>';
            }
            foreach ($se->Columns as $col => $settings) {
                if (is_array($item->$col)) {
                    continue;
                }
                if (is_object($item->$col)) {
                    $html .= '<td>' . Dates::Datestamp($item->$col) . '</td>';
                } else {
                    $html .= '<td>' . ($item->$col) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }
}