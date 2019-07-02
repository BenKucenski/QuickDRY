<?php

/**
 * Class MSAccess
 */
class MSAccess
{
	private $conn = null;

    /**
     * @param $file
     * @param string $user
     * @param string $pass
     */
    public function __construct($file, $user = '', $pass = '', $is_dsn = false)
    {
        if(!defined('MS_ACCESS_DRIVER')) {
            define('MS_ACCESS_DRIVER', 'odbc:Driver={Microsoft Access Driver (*.mdb)}');
        }
		// define('MS_ACCESS_DRIVER','odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)}');

        // https://www.freethinkingdesign.co.uk/blog/accessing-access-db-file-accdb-windows-64bit-via-php-using-odbc/ -- read this on 64 bit php
        // https://www.microsoft.com/en-us/download/details.aspx?id=23734 -- install this if you have 64 bit php

        // https://support.microsoft.com/en-us/help/295297/prb-error-message-0x80004005-general-error-unable-to-open-registry-key
        $str = [];
        if(!$is_dsn) {
            $str[] = MS_ACCESS_DRIVER;
            $str[] = 'Dbq=' . $file;
        } else {
            $str[] = $file;
        }

        if($user) {
            $str[] = 'Uid=' . $user;
        }

        if($pass) {
            $str[] = 'PWD=' . $pass;
        }

	try {
        $this->conn = new PDO(implode(';', $str));
        } catch(Exception $ex) {
        	Halt($ex);
        }
    }

	function Disconnect()
	{
		if(!is_null($this->conn))
		{
			$this->conn = null;
		}
	}

    /**
     * @param $sql
     * @param array $params
     * @return array
     */
    function Query($sql, $params = [])
	{
		$returnval = ['error' => false, 'numrows' => 0, 'data' => []];
		$returnval['sql'] = $sql;
		$returnval['params'] = $params;
		
		try {
			if(!$this->conn)
				exit('database failure');
			$stmt = $this->conn->prepare($sql);
			if(!is_object($stmt)) {
				throw new exception('Failure to Query');
			}
			$stmt->execute($params);
		}
		catch (Exception $e) {
			Debug::Halt($e);
		}
		$returnval['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$returnval['numrows'] = count($returnval['data']);
		
		$stmt->closeCursor();
		$stmt = null;
		
		
		return $returnval;
	}
}

function autoloader_QuickDRY_ACCESS($class)
{
    $class_map = [
        'ACCESS_Connection' => 'access/ACCESS_Connection.php',
        'ACCESS_A' => 'access/ACCESS_A.php',
        'ACCESS_B' => 'access/ACCESS_B.php',
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