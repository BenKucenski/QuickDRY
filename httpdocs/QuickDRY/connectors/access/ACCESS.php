<?php
namespace QuickDRY\Connectors;
use QuickDRY\Utilities\Debug;

/**
 * Class ACCESS
 *
 * @property resource connection
 */
class ACCESS
{
    protected $connection = null;
    protected string $ACCESS_FILE;
    protected ?string $ACCESS_USER;
    protected ?string $ACCESS_PASS;

    public function __destruct()
    {
        odbc_close($this->connection);
    }

    public function EscapeQuery($sql, $params)
    {
        if ($params && is_array($params)) {
            foreach ($params as $k => $v) {
                $v = str_replace('\'', '\'\'', $v);
                $sql = str_replace('@' . $k, '\'' . $v . '\'', $sql);
            }
        }
        return $sql;
    }

    public function __construct($file, $user = null, $pass = null)
    {
        $this->ACCESS_FILE = $file;
        $this->ACCESS_USER = $user;
        $this->ACCESS_PASS = $pass;

        $this->connection = odbc_connect('Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=' . $file, $user, $pass);
    }

    public function Query($sql, $params = null): array
    {
        if (!$this->connection) {
            Debug::Halt('Not Connected');
        }

        $query = $this->EscapeQuery($sql, $params);

        $res = @odbc_exec($this->connection, $query);
        if (odbc_error($this->connection)) {
            Debug::Halt(['sql' => $sql, 'params' => $params, 'query' => $query, 'error' => 'ACCESS', 'odbc_errormsg' => odbc_errormsg($this->connection)]);
        }

        $list = [];
        while ($row = odbc_fetch_array($res)) {
            $list[] = $row;
        }
        return $list;
    }

    public function Execute($sql, $params = null)
    {
        if (!$this->connection) {
            Debug::Halt('Not Connected');
        }

        $query = $this->EscapeQuery($sql, $params);

        @odbc_exec($this->connection, $query);
        if (odbc_error($this->connection)) {
            Debug::Halt(['sql' => $sql, 'params' => $params, 'query' => $query, 'error' => 'ACCESS', 'odbc_errormsg' => odbc_errormsg($this->connection)]);
        }
    }

    public function QueryMap($sql, $params, $func, $return = true): array
    {
        if (!$this->connection) {
            Debug::Halt('Not Connected');
        }

        $query = $this->EscapeQuery($sql, $params);

        $res = @odbc_exec($this->connection, $query);
        if (odbc_error($this->connection)) {
            Debug::Halt(['sql' => $sql, 'params' => $params, 'query' => $query, 'error' => 'ACCESS', 'odbc_errormsg' => odbc_errormsg($this->connection)]);
        }

        $list = [];
        while ($row = odbc_fetch_array($res)) {
            if ($return) {
                $list[] = call_user_func($func, $row);
            } else {
                call_user_func($func, $row);
            }
        }
        return $list;
    }
}