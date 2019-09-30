<?php
/** DO NOT USE THIS CLASS DIRECTLY **/
define('GUID_MSSQL','UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) ');

if (!function_exists('sqlsrv_connect')) {
    function sqlsrv_connect()
    {
        exit('sqlsrv_connect not loaded as an extension and is not actually available.');
    }
}

if (!function_exists('sqlsrv_query')) {
    function sqlsrv_query()
    {
        exit('sqlsrv_query not loaded as an extension and is not actually available.');
    }
}

/**
 * Class MSSQL
 */
class MSSQL extends SafeClass
{
    /**
     * @param $data
     *
     * @return string
     */
    public static function EscapeString($data)
    {
        if (is_array($data)) {
            Halt($data);
        }
        if (is_numeric($data)) return "'" . $data . "'";

        if ($data instanceof DateTime) {
            $data = Dates::Timestamp($data);
        }

        $non_displayables = [
//            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15 // this breaks LIKE '%001' for example
//            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        ];
        foreach ($non_displayables as $regex) {
            $data = preg_replace($regex, '', $data);
        }
        $data = str_replace("'", "''", $data);
        if (strcasecmp($data, 'null') == 0) {
            return 'null';
        }

        $data = str_replace('{{{', '', $data);
        $data = str_replace('}}}', '', $data);

        return "'" . $data . "'";
    }

    /**
     * @param $sql
     * @param $params
     *
     * @return mixed
     */
    public static function EscapeQuery($sql, $params, $test =  false)
    {
        $pattern = '/@([\w\d]*)?/si';
        if($test) {
            $matches = [];
            preg_match_all($pattern, $sql, $matches);
            CleanHalt($matches);
        }
        $count = 0;
        return preg_replace_callback($pattern, function ($result)
        use ($params, &$count, $sql) {
            if (isset($result[1])) {
                if (isset($params[$count])) {
                    $count++;
                    switch ($result[1]) {
                        case 'nullstring':
                            if (!$params[$count - 1] || $params[$count - 1] === 'null') {
                                return 'null';
                            }
                            return MSSQL::EscapeString($params[$count - 1]);

                        case 'nullnumeric':
                            if (!$params[$count - 1] || $params[$count - 1] === 'null') {
                                return 'null';
                            }
                            return $params[$count - 1] * 1.0;

                        case 'nq':
                            return $params[$count - 1];

                        default:
                            return MSSQL::EscapeString($params[$count - 1]);
                    }
                }

                if (isset($params[$result[1]])) {
                    if(Strings::EndsWith($result[1],'_NQ')) {
                        return $params[$result[1]];
                    }
                    return MSSQL::EscapeString($params[$result[1]]);
                } else {
                    // in order to allow more advanced queries that Declare Variables, we just need to ignore @Var if it's not in the passed in parameters
                    // SQL Server will return an error if there really is one
                    return '@' . $result[1];
                }
                //throw new Exception(print_r([json_encode($params, JSON_PRETTY_PRINT), $count, $result, $sql], true) . ' does not have a matching parameter (ms_escape_query).');
            }
            return '';
        }, $sql);
    }
}

function autoloader_QuickDRY_MSSQL($class)
{
    $class_map = [
        'MSSQL_Core' => 'mssql/MSSQL_Core.php',
        'MSSQL_TableColumn' => 'mssql/MSSQL_TableColumn.php',
        'MSSQL_ForeignKey' => 'mssql/MSSQL_ForeignKey.php',
        'MSSQL_Connection' => 'mssql/MSSQL_Connection.php',
        'MSSQL_A' => 'mssql/MSSQL_A.php',
        'MSSQL_B' => 'mssql/MSSQL_B.php',
        'MSSQL_C' => 'mssql/MSSQL_C.php',
        'MSSQL_Queue' => 'mssql/MSSQL_Queue.php',
        'MSSQL_StoredProcParam' => 'mssql/MSSQL_StoredProcParam.php',
        'MSSQL_StoredProc' => 'mssql/MSSQL_StoredProc.php',
        'MSSQL_CodeGen' => 'mssql/MSSQL_CodeGen.php',
    ];


    if (!isset($class_map[$class])) {
        return;
    }

    $file = $class_map[$class];
    $file = 'QuickDRY/connectors/' . $file;

    if (file_exists($file)) { // web
        require_once $file;
    } else {
        if (file_exists('../' . $file)) { // cron folder
            require_once '../' . $file;
        } else { // scripts folder
            require_once '../httpdocs/' . $file;
        }
    }
}


spl_autoload_register('autoloader_QuickDRY_MSSQL');
