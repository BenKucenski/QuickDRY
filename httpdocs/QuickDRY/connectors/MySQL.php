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
    public static function EscapeQuery($conn, $sql, $params) {

        if(is_null($conn)) {
            Halt('no connection');
        }
        $count = 0;
        return preg_replace_callback("/\{\{(.*?)\}\}/i", function ($result)
        use ($params, &$count, $conn, $sql) {
            if (isset($result[1])) {

                if(isset($params[$count])) {
                    $count++;
                    if($result[1] !== 'nq') {
                        return '"' . mysqli_escape_string($conn, $params[$count - 1]) . '"';
                    } else {
                        return $params[$count - 1]; // don't use mysqli_escape_string here because it will escape quotes which breaks things
                    }
                }

                if(isset($params[$result[1]]))
                    return '"' . mysqli_escape_string($conn, $params[$result[1]]) . '"';

                Halt(array($sql, $params), $result[0] . ' does not having a matching parameter (mysql_escape_query).');
            }
            return null;
        }, $sql);
    }
}


require_once 'mysql/MySQL_Core.php';
require_once 'mysql/MySQL_TableColumn.php';
require_once 'mysql/MySQL_ForeignKey.php';
require_once 'mysql/MySQL_Connection.php';
require_once 'mysql/MySQL_A.php';
require_once 'mysql/MySQL_B.php';
require_once 'mysql/MySQL_C.php';
require_once 'mysql/MySQL_Queue.php';
require_once 'mysql/MySQL_StoredProcParam.php';
require_once 'mysql/MySQL_StoredProc.php';
require_once 'mysql/MySQL_CodeGen.php';

