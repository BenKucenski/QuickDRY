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
        if($row) {
            $this->HaltOnError(false);
            $this->FromRow($row);
            if($this->HasMissingProperties()) {
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
        $class = get_called_class();
        $cols = array_keys(get_class_vars($class));
        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = $class;
        $se->Columns = [];
        foreach($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(null, $col);
        }
        return $se;
    }

    /**
     * @param SimpleReport[] $items
     * @return string
     */
    public static function ToHTML(&$items)
    {
        $class = get_called_class();
        $cols = array_keys(get_class_vars($class));
        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = $class;
        $se->Columns = [];
        foreach($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(null, $col);
        }

        $html = '<table><thead><tr>';
        foreach($se->Columns as $col => $settings) {
            $html .='<th>' . $col . '</th>';
        }
        $html .='</tr></thead><tbody>';
        foreach($se->Report as $item) {
            $html .='<tr>';
            foreach($se->Columns as $col => $settings) {
                if(is_object($item->$col)) {
                    $html .= '<td>' . Dates::Datestamp($item->$col) . '</td>';
                } else {
                    $html .= '<td>' . ($item->$col) . '</td>';
                }
            }
            $html .='</tr>';
        }

        return $html . '</tbody></table>';
    }
}