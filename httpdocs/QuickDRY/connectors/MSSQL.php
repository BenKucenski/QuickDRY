<?php
/** DO NOT USE THIS CLASS DIRECTLY **/

if(!function_exists('sqlsrv_connect')) {
    function sqlsrv_connect() {
        exit('sqlsrv_connect not loaded as an extension and is not actually available.');
    }
}

if(!function_exists('sqlsrv_query')) {
    function sqlsrv_query() {
        exit('sqlsrv_query not loaded as an extension and is not actually available.');
    }
}

/**
 * @param $data
 *
 * @return string
 */
function ms_escape_string($data) {
	if(is_array($data)) {
		Halt($data);
	}
    if ( is_numeric($data) ) return "'" . $data . "'";

    if($data instanceof DateTime) {
        $data = Timestamp($data);
    }

    $non_displayables = [
        '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
        '/%1[0-9a-f]/',             // url encoded 16-31
        '/[\x00-\x08]/',            // 00-08
        '/\x0b/',                   // 11
        '/\x0c/',                   // 12
        '/[\x0e-\x1f]/'             // 14-31
    ];
    foreach ( $non_displayables as $regex ) {
        $data = preg_replace($regex, '', $data);
    }
    $data = str_replace("'", "''", $data );
    if(strcasecmp($data,'null') == 0) {
        return 'null';
    }

    return "'" . $data . "'";
}

/**
 * @param $sql
 * @param $params
 *
 * @return mixed
 */
function ms_escape_query($sql, $params) {
    $count = 0;
    return preg_replace_callback("/\{\{(.*?)\}\}/i", function ($result)
    use ($params, &$count, $sql) {
        if (isset($result[1])) {
            if(isset($params[$count])) {
                $count++;
                switch($result[1]) {
                	case 'nullstring':
                		if(!$params[$count-1] || $params[$count - 1] === 'null') {
                			return 'null';
                		}
                		return ms_escape_string($params[$count-1]);

                	case 'nullnumeric':
                		if(!$params[$count-1] || $params[$count - 1] === 'null') {
                			return 'null';
                		}
                		return $params[$count-1] * 1.0;

                	case 'nq':
               			return $params[$count-1];

                	default:
                		return ms_escape_string($params[$count-1]);
                }
            }

            if(isset($params[$result[1]]))
                return ms_escape_string($params[$result[1]]);

            throw new Exception(print_r([json_encode($params,JSON_PRETTY_PRINT),$count,$result, $sql],true) . ' does not have a matching parameter (ms_escape_query).');
        }
        return '';
    }, $sql);
}


require_once 'mssql/MSSQL_ChangeLog.php';
require_once 'mssql/MSSQL_Core.php';
require_once 'mssql/MSSQL_TableColumn.php';
require_once 'mssql/MSSQL_ForeignKey.php';
require_once 'mssql/MSSQL_Connection.php';
require_once 'mssql/MSSQL_A.php'; // edge
require_once 'mssql/MSSQL_B.php'; // datacenter - unused
require_once 'mssql/MSSQL_Queue.php'; // datacenter - unused
require_once 'mssql/MSSQL_CodeGen.php';
