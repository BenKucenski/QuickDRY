<?php

/**
 * Class SQL_Query
 *
 * @property array $Params
 * @property string SQL
 */
class SQL_Query extends SafeClass
{
    public $SQL;
    public $Params;

    public function __construct($sql, $params)
    {
        $this->SQL = $sql;
        $this->Params = $params;
    }
}