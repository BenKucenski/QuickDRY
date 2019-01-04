<?php
/**
 * Class ACCESS_Connection
 */
class ACCESS_Connection
{
    private $conn = null;

    /**
     * @param $file
     * @param string $user
     * @param string $pass
     */
    public function __construct($file, $user = '', $pass = '', $is_dsn = false)
    {
        // one of these should work depending on which version of ACCESS Driver you have installed
        // define('ACCESS_DRIVER','odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}');
        // define('ACCESS_DRIVER','odbc:Driver={Microsoft Access Driver (*.mdb)}');

        if (!defined('ACCESS_DRIVER')) {
            Halt('ACCESS_DRIVER must be defined.  See source code of ACCESS_Connection.php');
        }
        // https://www.freethinkingdesign.co.uk/blog/accessing-access-db-file-accdb-windows-64bit-via-php-using-odbc/ -- read this on 64 bit php
        // https://www.microsoft.com/en-us/download/details.aspx?id=23734 -- install this if you have 32 bit php
        // https://www.microsoft.com/en-us/download/confirmation.aspx?id=13255 -- install this if you have 64 bit php

        // https://support.microsoft.com/en-us/help/295297/prb-error-message-0x80004005-general-error-unable-to-open-registry-key

        // https://stackoverflow.com/questions/31813574/pdo-returning-error-could-not-find-driver-with-a-known-working-dsn
        // if you get an error about driver not found, check that extension=php_pdo_odbc.dll is in your php.ini
        $str = [];
        if (!$is_dsn) {
            $str[] = ACCESS_DRIVER;
            $str[] = 'Dbq=' . $file; // do not put quotes around file name even if there are spaces
        } else {
            $str[] = $file;
        }

        if ($user) {
            $str[] = 'Uid=' . $user;
        }

        if ($pass) {
            $str[] = 'PWD=' . $pass;
        }

        try {

            $this->conn = new PDO(implode(';', $str));
        } catch (Exception $ex) {
            Halt($ex);
        }
    }

    function Disconnect()
    {
        if (!is_null($this->conn)) {
            $this->conn = null;
        }
    }

    /**
     * @param $sql
     * @param array $params
     * @return array
     */
    function Query($sql, $params = [], $map_function = null)
    {
        $returnval = ['numrows' => 0, 'data' => []];
        $returnval['sql'] = $sql;
        $returnval['params'] = $params;

        try {
            if (!$this->conn)
                exit('database failure');
            $stmt = $this->conn->prepare($sql);
            if (!is_object($stmt)) {
                throw new exception('Failure to Query');
            }
            $stmt->execute($params);
        } catch (Exception $e) {
            Debug::Halt($e);
        }
        $returnval['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($map_function) {
            $list =[];
            foreach($returnval['data'] as $r) {
                 $list[] = call_user_func($map_function, $r);
            }
            return $list;
        }
        $returnval['numrows'] = count($returnval['data']);

        $stmt->closeCursor();
        $stmt = null;


        return $returnval;
    }

    public function SetDatabase($db_base)
    {
        return null;
    }

    /**
     * @return array
     */
    public function GetTables()
    {
        // this doesn't work as there is no permission to access MSysObjects
        // TODO: sort out how to get SCHEMA information in PHP
        $sql = '
SELECT MSysObjects.Name AS TABLE_NAME
FROM MSysObjects
WHERE (((Left([Name],1))<>"~") 
        AND ((Left([Name],4))<>"MSys") 
        AND ((MSysObjects.Type) In (1,4,6)))
order by MSysObjects.Name         
        ';
        $res = $this->Query($sql);
        $list = [];
        foreach($res['data'] as $row)
        {
            $t = $row['TABLE_NAME'];
            if(substr($t,0,strlen('TEMP')) === 'TEMP')
                continue;

            $list[] = $t;
        }
        return $list;
    }

}