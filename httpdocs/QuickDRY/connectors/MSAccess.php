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
    public function __construct($file, $user = '', $pass = '')
	{
		$str = [];
		$str[] = 'odbc:Driver={Microsoft Access Driver (*.mdb)}';
		$str[] = 'Dbq=' . $file;
		if($user)
			$str[] = 'Uid=' . $user;
		
		if($pass)
			$str[] = 'PWD=' . $pass;
		
		$this->conn = new PDO(implode(';', $str));
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