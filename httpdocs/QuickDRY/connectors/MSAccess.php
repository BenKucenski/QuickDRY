<?php

/**
 * Class MSAccess
 */
class MSAccess
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
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
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
                            return self::EscapeString($params[$count - 1]);

                        case 'nullnumeric':
                            if (!$params[$count - 1] || $params[$count - 1] === 'null') {
                                return 'null';
                            }
                            return $params[$count - 1] * 1.0;

                        case 'nq':
                            return $params[$count - 1];

                        default:
                            return self::EscapeString($params[$count - 1]);
                    }
                }

                if (isset($params[$result[1]])) {
                    if(Strings::EndsWith($result[1],'_NQ')) {
                        return $params[$result[1]];
                    }
                    return self::EscapeString($params[$result[1]]);
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

function autoloader_QuickDRY_ACCESS($class)
{
    $class_map = [
        'ACCESS_Connection' => 'access/ACCESS_Connection.php',
        'ACCESS_A' => 'access/ACCESS_A.php',
        'ACCESS_B' => 'access/ACCESS_B.php',
        'ACCESS_X' => 'access/ACCESS_X.php',
        'ACCESS_CodeGen' => 'access/ACCESS_CodeGen.php',
        'ACCESS_Core' => 'access/ACCESS_Core.php',
        'ACCESS_TableColumn' => 'access/ACCESS_TableColumn.php',
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


spl_autoload_register('autoloader_QuickDRY_ACCESS');