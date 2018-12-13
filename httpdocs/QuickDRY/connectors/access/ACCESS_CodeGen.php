<?php

/**
 * Class ACCESS_CodeGen
 */
class ACCESS_CodeGen extends SQLCodeGen
{
    public function Init($database, $database_constant, $user_class, $user_var, $user_id_column, $master_page, $lowercase_tables, $use_fk_column_name, $DatabaseClass = 'ACCESS_A', $GenerateJSON = true, $DestinationFolder = '../httpdocs')
    {
        $this->DatabaseTypePrefix = 'access';

        if (!$DatabaseClass) {
            $DatabaseClass = 'ACCESS_A';
        }
        if (!class_exists($DatabaseClass)) {
            exit($DatabaseClass . ' is invalid');
        }

        $this->DestinationFolder = $DestinationFolder;
        $this->DatabaseClass = $DatabaseClass;
        $this->Database = $database;
        $this->DatabaseConstant = $database_constant;
        $this->UserClass = $user_class ? $user_class : 'UserClass';
        $this->UserVar = $user_var ? $user_var : 'CurrentUser';
        $this->UserIdColumn = $user_id_column ? $user_id_column : 'id';
        $this->MasterPage = $master_page ? $master_page : 'MASTERPAGE_DEFAULT';
        $this->DatabasePrefix = $this->DatabaseConstant ? $this->DatabaseConstant : $this->Database;
        $this->LowerCaseTables = $lowercase_tables;
        $this->UseFKColumnName = $use_fk_column_name;
        $this->GenerateJSON = $GenerateJSON;

        // not really a database name, just set for compatibility with code generation
        //$DatabaseClass::SetDatabase($this->Database);

        $this->Tables = $DatabaseClass::GetTables();

        $this->CreateDirectories();
    }


    /**
     * @return array
     */
    function GenerateDatabaseClass()
    { // no stored procedures
        return [];
    }
}