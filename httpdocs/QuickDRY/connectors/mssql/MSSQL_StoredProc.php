<?php

class MSSQL_StoredProc extends SafeClass
{
    public $SPECIFIC_CATALOG;
    public $SPECIFIC_SCHEMA;
    public $SPECIFIC_NAME;
    public $ROUTINE_CATALOG;
    public $ROUTINE_SCHEMA;
    public $ROUTINE_NAME;
    public $ROUTINE_TYPE;
    public $MODULE_CATALOG;
    public $MODULE_SCHEMA;
    public $MODULE_NAME;
    public $UDT_CATALOG;
    public $UDT_SCHEMA;
    public $UDT_NAME;
    public $DATA_TYPE;
    public $CHARACTER_MAXIMUM_LENGTH;
    public $CHARACTER_OCTET_LENGTH;
    public $COLLATION_CATALOG;
    public $COLLATION_SCHEMA;
    public $COLLATION_NAME;
    public $CHARACTER_SET_CATALOG;
    public $CHARACTER_SET_SCHEMA;
    public $CHARACTER_SET_NAME;
    public $NUMERIC_PRECISION;
    public $NUMERIC_PRECISION_RADIX;
    public $NUMERIC_SCALE;
    public $DATETIME_PRECISION;
    public $INTERVAL_TYPE;
    public $INTERVAL_PRECISION;
    public $TYPE_UDT_CATALOG;
    public $TYPE_UDT_SCHEMA;
    public $TYPE_UDT_NAME;
    public $SCOPE_CATALOG;
    public $SCOPE_SCHEMA;
    public $SCOPE_NAME;
    public $MAXIMUM_CARDINALITY;
    public $DTD_IDENTIFIER;
    public $ROUTINE_BODY;
    public $ROUTINE_DEFINITION;
    public $EXTERNAL_NAME;
    public $EXTERNAL_LANGUAGE;
    public $PARAMETER_STYLE;
    public $IS_DETERMINISTIC;
    public $SQL_DATA_ACCESS;
    public $IS_NULL_CALL;
    public $SQL_PATH;
    public $SCHEMA_LEVEL_ROUTINE;
    public $MAX_DYNAMIC_RESULT_SETS;
    public $IS_USER_DEFINED_CAST;
    public $IS_IMPLICITLY_INVOCABLE;
    public $CREATED;
    public $LAST_ALTERED;

    public function __construct($row = null)
    {
        if($row) {
            $this->FromRow($row);
        }
    }
}