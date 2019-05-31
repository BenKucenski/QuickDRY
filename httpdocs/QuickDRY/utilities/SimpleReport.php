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
}