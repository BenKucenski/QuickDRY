<?php

use QuickDRY\Utilities\SafeClass;

/**
 * Class SQL_Query
 *
 * @property array $Params
 * @property string SQL
 */
class SQL_Query extends SafeClass
{
    public string $SQL;
    public ?array $Params;

    public function __construct(string $sql, array $params = null)
    {
        $this->SQL = $sql;
        $this->Params = $params;
    }
}