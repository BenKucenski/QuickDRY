<?php
/**
 * Class MSSQL_ForeignKey
 */
class MSSQL_ForeignKey
{
    public $database_name;
    public $table_name;
    public $column_name;
    public $foreign_table_name;
    public $foreign_column_name;
    public $FK_CONSTRAINT_NAME;
    public $REFERENCED_CONSTRAINT_NAME;
    public $REFERENCED_COLUMN_ID;

    /**
     * @param $row
     */
    public function FromRow(&$row)
    {
        foreach($row as $key => $value)
        {
            switch($key)
            {
                case 'FK_TABLE_NAME': $this->table_name = $value; break;
                case 'FK_DATABASE_NAME': $this->database_name = $value; break;
                case 'FK_COLUMN_NAME': $this->column_name = $value; break;
                case 'REFERENCED_TABLE_NAME': $this->foreign_table_name = $value; break;
                case 'REFERENCED_COLUMN_NAME': $this->foreign_column_name = $value; break;
                case 'FK_CONSTRAINT_NAME': $this->FK_CONSTRAINT_NAME = $value; break;
                case 'REFERENCED_CONSTRAINT_NAME': $this->REFERENCED_CONSTRAINT_NAME = $value; break;
                case 'REFERENCED_COLUMN_ID': $this->REFERENCED_COLUMN_ID = $value; break;
            }
        }
    }

    public function AddRow($row)
    {
        if(!is_array($this->column_name)) {
            $this->column_name = [$this->column_name];
        }

        if(!is_array($this->foreign_column_name)) {
            $this->foreign_column_name = [$this->foreign_column_name];
        }

        $this->column_name[$row['REFERENCED_COLUMN_ID']] = $row['FK_COLUMN_NAME'];
        $this->foreign_column_name[$row['REFERENCED_COLUMN_ID']] = $row['REFERENCED_COLUMN_NAME'];
        ksort($this->column_name);
        ksort($this->foreign_column_name);
    }
}
