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

    public static function GetAll($where)
    {
        $res = self::SimpleSearch($where);
        $list = [];
        $return_type = get_called_class();
        foreach($res['data'] as $row) {
            $l = new $return_type();
            $l->FromRow($row);
            $list[] = $l;
        }
        return $list;
    }

    public static function Get($where)
    {
        $res = self::SimpleSearch($where, 0 ,1);
        $return_type = get_called_class();
        foreach($res['data'] as $row) {
            $l = new $return_type();
            $l->FromRow($row);
            return $l;
        }
        return null;
    }
}