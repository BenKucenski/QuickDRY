<?php

class MSSQL_CodeGen extends SQLCodeGen
{
    public function Init($database, $database_constant, $user_class, $user_var, $user_id_column, $master_page, $lowercase_tables, $use_fk_column_name, $DatabaseClass = 'MSSQL_A')
    {
        $this->DatabaseTypePrefix = 'ms';

        if (!$DatabaseClass) {
            $DatabaseClass = 'MSSQL_A';
        }
        if (!class_exists($DatabaseClass)) {
            exit($DatabaseClass . ' is invalid');
        }

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

        $DatabaseClass::SetDatabase($this->Database);

        $this->Tables = $DatabaseClass::GetTables();

        $this->CreateDirectories();
    }


    function GenerateDatabaseClass()
    {
        $DatabaseClass = $this->DatabaseClass;
        $class_name = $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);
        $stored_procs = $DatabaseClass::GetStoredProcs();

        if (!$stored_procs) {
            return false;
        }
        $sp_require = [];
        $sp_code = [];
        foreach ($stored_procs as $sp) {
            $sp_class = $class_name . '_' . $sp->SPECIFIC_NAME . 'Class';

            $this->GenerateSPClassFile($sp_class);

            $sp_require[] = 'require_once \'' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp/' . $sp_class . '.php\';';

            $sp_params = $DatabaseClass::GetStoredProcParams($sp->SPECIFIC_NAME);
            $params = [];
            $sql_params = [];
            $func_params = [];
            foreach ($sp_params as $param) {
                $clean_param = str_replace('#', '_', str_replace('@', '$', $param->Parameter_name));
                $sql_param = str_replace('$', '@', $clean_param);
                $func_params[] = $clean_param;
                $sql_params[] = $sql_param;
                $params[] = '\'' . str_replace('@', '', $sql_param) . '\' => ' . $clean_param;
            }

            $sp_code[] = '

    /**
     * @return ' . $sp_class . '[]
     */
    public static function ' . $sp->SPECIFIC_NAME . '(' . implode(', ', $func_params) . ')
    {
        $sql = \'
        EXEC	\' . ' . $this->DatabaseConstant . ' . \'.[dbo].[' . $sp->SPECIFIC_NAME . ']
        ' . implode(", ", $sql_params) . '

        \';
        /* @var $rows ' . $sp_class . '[] */
        $rows = ' . $DatabaseClass . '::Query($sql, [' . implode(', ', $params) . '], null, function ($row) {
            return new ' . $sp_class . '($row);
        });

        if (isset($rows[\'error\'])) {
            Halt($rows);
        }
        return $rows;
    }
            ';
        }
        $code = '<?php
' . implode("\r\n", $sp_require) . '

class sp_' . $class_name . ' extends ' . $DatabaseClass . '
{
' . implode("\r\n", $sp_code) . '
}
        ';

        $fp = fopen('_common/sp_' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '.php', 'w');
        fwrite($fp, $code);
        fclose($fp);

        return true;
    }
}