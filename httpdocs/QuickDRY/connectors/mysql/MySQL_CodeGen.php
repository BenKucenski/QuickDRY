<?php

/**
 * Class MySQL_CodeGen
 */
class MySQL_CodeGen extends SQLCodeGen
{
    public function Init($database, $database_constant, $user_class, $user_var, $user_id_column, $master_page, $lowercase_tables, $use_fk_column_name, $DatabaseClass = 'MySQL_A', $GenerateJSON = true, $DestinationFolder = '../httpdocs')
    {
        $this->DatabaseTypePrefix = 'my';

        if (!$DatabaseClass) {
            $DatabaseClass = 'MySQL_A';
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

        $DatabaseClass::CopyInfoSchema();

        $DatabaseClass::SetDatabase($this->Database);

        Log::Insert('$this->Tables = ' . $DatabaseClass . '::GetTables();', true);
        $this->Tables = $DatabaseClass::GetTables();

        $this->CreateDirectories();
    }

    /**
     * @return bool
     */
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
            $sp_class = SQL_Base::TableToClass($this->DatabasePrefix, $sp->SPECIFIC_NAME, true, $this->DatabaseTypePrefix . '_sp');

            Log::Insert($sp_class, true);

            $this->GenerateSPClassFile($sp_class);

            $sp_require['db_' . $sp_class] = 'common/' . $class_name . '/sp_db/db_' . $sp_class . '.php';
            $sp_require[$sp_class] = 'common/' . $class_name . '/sp/' . $sp_class . '.php';

            $sp_params = $DatabaseClass::GetStoredProcParams($sp->SPECIFIC_NAME);
            $params = [];
            $sql_params = [];
            $func_params = [];
            foreach ($sp_params as $param) {
                $clean_param = str_replace('#', '_', str_replace('@', '$', $param->Parameter_name));
                $sql_param = '{{' . str_replace('$', '', $clean_param) . '}}';
                $func_params[] = $clean_param;
                $sql_params[] = $sql_param;
                $params[] = '\'' . str_replace('$', '', $clean_param) . '\' => ' . $clean_param;
            }

            $code = '<?php
            
/**
 * Class db_' . $sp_class . '
 */    
 class db_' . $sp_class . ' extends SafeClass
{
    /**
     * @return ' . $sp_class . '[]
     */
    public static function GetReport(' . implode(', ', $func_params) . ')
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
}
        ';

            $file = $this->CommonClassSPDBFolder . '/db_' . $sp_class . '.php';
            $fp = fopen($file, 'w');
            fwrite($fp, $code);
            fclose($fp);
        }

        return $sp_require;
    }
}