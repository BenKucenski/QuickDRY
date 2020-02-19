<?php

/**
 * Class SimpleReport
 */
class SimpleReport extends SafeClass
{
    /**
     * SimpleReport constructor.
     * @param null $row
     */
    public function __construct($row = null)
    {
        if ($row) {
            $this->HaltOnError(false);
            $this->FromRow($row);
            if ($this->HasMissingProperties()) {
                Halt($this->GetMissingPropeties());
            }
            $this->HaltOnError(true);
        }
    }

    /**
     * @param SimpleReport[] $items
     * @return SimpleExcel
     */
    public static function ToExcel(&$items)
    {
        if(!sizeof($items)) {
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
     * @param SimpleReport[] $items
     * @return string
     */
    public static function ToHTML(&$items, $class = '', $style = '', $numbered = false, $limit = 0)
    {
        if(!sizeof($items)) {
            return '';
        }

        $obj_class = get_called_class();
        $cols = array_keys(get_object_vars($items[0]));

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = $obj_class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(null, $col);
        }

        $html = '<table class="' . $class . '" style="' . $style . '"><thead><tr>';
        if ($numbered) {
            $html .= '<th></th>';
        }
        foreach ($se->Columns as $col => $settings) {
            $html .= '<th>' . $col . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($se->Report as $i => $item) {
            if($limit && $i >= $limit) {
                break;
            }
            $html .= '<tr>';
            if ($numbered) {
                $html .= '<td>' . ($i + 1) . '</td>';
            }
            foreach ($se->Columns as $col => $settings) {
                if(is_array($item->$col)) {
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