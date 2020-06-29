<?php

/**
 * Class MySQL
 */
class MySQL extends SafeClass
{
    /**
     * @param $conn
     * @param $sql
     * @param $params
     *
     * @return mixed
     */
    public static function EscapeQuery($conn, $sql, $params)
    {

        if (is_null($conn)) {
            Halt('QuickDRY Error: No MySQL Connection');
        }
        $count = 0;
        return preg_replace_callback("/\{\{(.*?)\}\}/i", function ($result)
        use ($params, &$count, $conn, $sql) {
            if (isset($result[1])) {

                if (isset($params[$count])) {
                    $count++;
                    if ($result[1] !== 'nq') {
                        return '"' . mysqli_escape_string($conn, $params[$count - 1]) . '"';
                    } else {
                        return $params[$count - 1]; // don't use mysqli_escape_string here because it will escape quotes which breaks things
                    }
                }

                if (isset($params[$result[1]])) {
                    if(is_array($params[$result[1]])) {
                        Halt(['QuickDRY Error: Parameter cannot be array', $params]);
                    }
                    return '"' . mysqli_escape_string($conn, $params[$result[1]]) . '"';
				}

                Halt(array($sql, $params), $result[0] . ' does not having a matching parameter (mysql_escape_query).');
            }
            return null;
        }, $sql);
    }

    public static function PasswordHash($input, $hex = true)
    {
        $sha1_stage1 = sha1($input, true);
        $output = sha1($sha1_stage1, !$hex);
        return '*' . strtoupper($output);
    }
}

function autoloader_QuickDRY_MySQL($class)
{
    $class_map = [
        'MySQL_Core' => 'mysql/MySQL_Core.php',
        'MySQL_TableColumn' => 'mysql/MySQL_TableColumn.php',
        'MySQL_ForeignKey' => 'mysql/MySQL_ForeignKey.php',
        'MySQL_Connection' => 'mysql/MySQL_Connection.php',
        'MySQL_A' => 'mysql/MySQL_A.php',
        'MySQL_B' => 'mysql/MySQL_B.php',
        'MySQL_C' => 'mysql/MySQL_C.php',
        'MySQL_Queue' => 'mysql/MySQL_Queue.php',
        'MySQL_StoredProcParam' => 'mysql/MySQL_StoredProcParam.php',
        'MySQL_StoredProc' => 'mysql/MySQL_StoredProc.php',
        'MySQL_CodeGen' => 'mysql/MySQL_CodeGen.php',
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


spl_autoload_register('autoloader_QuickDRY_MySQL');





