<?php

/**
 * Class MSSQL_CodeGen
 */
class MSSQL_CodeGen extends SQLCodeGen
{
    public function Init($database, $database_constant, $user_class, $user_var, $user_id_column, $master_page, $lowercase_tables, $use_fk_column_name, $DatabaseClass = 'MSSQL_A', $GenerateJSON = true, $DestinationFolder = '../httpdocs')
    {
        $this->DatabaseTypePrefix = 'ms';

        if (!$DatabaseClass) {
            $DatabaseClass = 'MSSQL_A';
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

        $DatabaseClass::SetDatabase($this->Database);

        $this->Tables = $DatabaseClass::GetTables();

        $this->CreateDirectories();
    }

    public function DumpSchema()
    {
        $DatabaseClass = $this->DatabaseClass;

        if(!is_dir('_Schema')) {
            mkdir('_Schema');
        }

        $BaseFolder = '_Schema/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

        if(!is_dir($BaseFolder)) {
            mkdir($BaseFolder);
        }

        // this is everything, views, triggers, stored procs, etc
        /* @var $definitions MSSQL_Definition[] */
        $definitions = $DatabaseClass::GetDefinitions();
        if($definitions) {
            foreach($definitions as $definition) {
                Log::Insert('Definition: ' . $definition->object_name, true);
                $dest = $BaseFolder . '/_' . $definition->type_desc;
                if (!is_dir($dest)) {
                    mkdir($dest);
                }
                if($definition->table_name) {
                    $fp =  fopen($dest . '/' . $definition->table_name . '.' . $definition->object_name . '.txt','w');
                }
                else {
                    $fp =  fopen($dest . '/' . $definition->object_name . '.txt','w');
                }
                fwrite($fp, $definition->definition);
                fclose($fp);
            }
        }
    }

    /**
     * @return array
     */
    function GenerateDatabaseClass()
    {
        $DatabaseClass = $this->DatabaseClass;
        $class_name = $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

        /* @var $stored_procs MSSQL_StoredProc[] */
        $stored_procs = $DatabaseClass::GetStoredProcs();

        if (!$stored_procs) {
            return [];
        }
        $sp_require = [];
        foreach ($stored_procs as $sp) {
            $sp_class = SQL_Base::StoredProcToClass($this->DatabasePrefix, $sp->SPECIFIC_NAME, true, $this->DatabaseTypePrefix . '_sp');

            Log::Insert('Stored Proc: ' . $sp_class, true);

            $this->GenerateSPClassFile($sp_class);

            $sp_require['db_' . $sp_class] = 'common/' . $class_name . '/sp_db/db_' . $sp_class . '.php';
            $sp_require[$sp_class] = 'common/' . $class_name . '/sp/' . $sp_class . '.php';

            $sp_params = $DatabaseClass::GetStoredProcParams($sp->SPECIFIC_NAME);
            $params = [];
            $sql_params = [];
            $func_params = [];
            $clean_params = [];
            foreach ($sp_params as $param) {
                $clean_param = str_replace('$$', '$', str_replace('#', '_', str_replace('@', '$', $param->Parameter_name)));
                $clean_params[] = $clean_param;
                $sql_param = str_replace('$', '@', $clean_param);
                $func_params[] = $clean_param;
                $sql_params[] = $sql_param . ' -- ' . str_replace('$', '', $clean_param);
                $params[] = '\'' . str_replace('@', '', $sql_param) . '\' => ' . $clean_param;
            }

            $code = '<?php

/**
 * Class db_' . $sp_class . '
 */
class db_' . $sp_class . ' extends SafeClass
{
    public static $HaltSPOnError = true;
    /**
     * @param  ' . implode(PHP_EOL . '     * @param  ', $clean_params) . '
     * @return ' . $sp_class . '[]
     */
    public static function GetReport(' . implode(', ', $func_params) . ')
    {
        $sql = \'
        EXEC \' . ' . ($this->DatabaseConstant ? $this->DatabaseConstant : '\'[' . $this->Database . ']\'') . ' . \'.[dbo].[' . $sp->SPECIFIC_NAME . ']
        ' . implode("\n         ,", $sql_params) . '

        \';
        /* @var $rows ' . $sp_class . '[] */
        $rows = ' . $DatabaseClass . '::QueryMap($sql, [' . implode(', ', $params) . '], function ($row) {
            return new ' . $sp_class . '($row);
        });

        if (self::$HaltSPOnError && isset($rows[\'error\'])) {
            Halt($rows);
        }
        return $rows;
    }

    /**
     * @param  ' . implode(PHP_EOL . '     * @param  ', $clean_params) . '
     * @return array
     */
    public static function Exec(' . implode(', ', $func_params) . ')
    {
        $sql = \'
        EXEC \' . ' . ($this->DatabaseConstant ? $this->DatabaseConstant : '\'[' . $this->Database . ']\'') . ' . \'.[dbo].[' . $sp->SPECIFIC_NAME . ']
        ' . implode("\n         ,", $sql_params) . '

        \';
        $res = ' . $DatabaseClass . '::Execute($sql, [' . implode(', ', $params) . ']);

        if (self::$HaltSPOnError && $res[\'error\']) {
            Halt($res);
        }
        return $res;
    }
}
        ';
            $file = $this->CommonClassSPDBFolder . '/db_' . $sp_class . '.php';
            $fp = fopen($file, 'w');
            fwrite($fp, $code);
            fclose($fp);

            /**
            // put the SQL of the Stored Proc in a text file as given - no longer required, using separate folders for all types of definitions
            $file = $this->CommonClassSPDBFolder . '/db_' . $sp_class . '.txt';
            $fp = fopen($file, 'w');
            fwrite($fp, $sp->SOURCE_CODE);
            fclose($fp);
             * */
        }

        return $sp_require;
    }
}