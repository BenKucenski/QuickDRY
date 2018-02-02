<?php

/**
 * Class Elastic
 */
class Elastic_A extends Elastic_Core
{
    /**
     * @param      $where
     * @param int  $page
     * @param int  $per_page
     * @param null $order_by
     *
     * @return array
     */

    protected static $ACTIVE_ELASTIC_URL = Elastic_A_URL;
    protected static $ACTIVE_ELASTIC_HOST = Elastic_A_HOST;

    /**
     * @param $where
     * @param int $limit
     * @return array
     */
    public static function GetAll($where, $limit = 10000)
    {
        $return_type = get_called_class();

        $res = $return_type::Search($where, 0, 0);
        $count = $res['count'];
        if (!$count) {
            return [];
        }
        $list = [];
        $page = 0;
        $per_page = 10000; // arbitrary limit
        $max_page = ceil($count / $per_page);

        while ($page < $max_page && $page * $per_page < $limit) {
            Log::Insert([$page, $max_page], true);
            $res = $return_type::Search($where, $page, $per_page);
            foreach ($res['data'] as $row) {
                $list[] = new $return_type($row);
            }
            $page++;
        }
        return $list;
    }

    /**
     * @param $where
     * @param int $limit
     * @return array
     */
    public static function GetAllPaginated($where, $page, $per_page)
    {
        $return_type = get_called_class();

        $res = $return_type::Search($where, $page, $per_page);
        $count = $res['count'];
        if (!$count) {
            return [];
        }
        $list = [];
        foreach ($res['data'] as $row) {
            $list[] = new $return_type($row);
        }

        return $list;
    }

    /**
     * @param $where
     * @return int
     */
    public static function GetCount($where)
    {
        $return_type = get_called_class();

        $res = $return_type::Search($where, 0, 0);
        return $res['count'];
    }

    /**
     * @param $where
     * @return null
     */
    public static function Get($where)
    {
        $res = self::Search($where, 0 ,1);
        $return_type = get_called_class();
        foreach($res['data'] as $row) {
            return new $return_type($row);
        }
        return null;
    }
}