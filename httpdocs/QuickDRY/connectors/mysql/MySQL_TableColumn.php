<?php
/**
 * Class MSSQL_TableColumn
 */
class MySQL_TableColumn
{
    public $field;
    public $type;
    public $null;
    public $default;
    public $length = null;

    /**
     * @param $row
     */
    public function FromRow(&$row)
    {
        foreach($row as $key => $value)
        {
            switch($key)
            {
                case 'Field': $this->field = $value; break;
                case 'Type': $this->type = $value; break;
                case 'Null': $this->null = $value === 'YES' ? 1 : 0; break;
                case 'Default': $this->default = $value; break;
            }
        }
    }
}