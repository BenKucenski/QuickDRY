<?php
class Elastic_Core extends Elastic_Base
{
    /* @var $client Elasticsearch\Client */
    protected static $client = null;

    public static $use_log = false;
    public static $log = [];
    public static $query_count = 0;
    public static $query_time = 0;

    private static function Log($query, $time)
    {
        if (!self::$use_log) {
            return;
        }

        self::$query_count++;
        self::$query_time += $time;
        self::$log[] = ['query' => $query, 'time' => $time];
    }

    private static function _connect()
    {
        if (!static::$ACTIVE_ELASTIC_URL) {
            Halt('$ACTIVE_ELASTIC_URL is not set');
            return null;
        }

        if (is_null(static::$client)) {
            static::$client = new Elasticsearch\Client(['hosts' => [static::$ACTIVE_ELASTIC_URL]]);
        }

        if (!static::$client) {
            Halt('Could not connect to ' . static::$ACTIVE_ELASTIC_URL);
        }

        return static::$client;
    }

    public function Save()
    {
        if (!static::$_index) {
            Halt('static::$_index not defined');
        }

        if (!$this->_id) {
            $vars = [$this->ToArray(true)];
            $res = static::_Insert(static::$_index, static::$_type, $vars);
            $this->_id = $res['items'][0]['create']['_id'];
        } else {
            $vars = $this->ToArray();
            $vars = [$this->_id => $vars];
            $res = static::_InsertUpdate(static::$_index, static::$_type, $vars);
        }

        return $res;
    }

    /**
     * @param $json
     *
     * @return mixed|null
     */
    public static function InsertUpdate($index, $type, &$json)
    {
        return static::_InsertUpdate($index, $type, $json);
    }

    /**
     * @param $elastic_id
     *
     * @return mixed|null
     */
    public static function Remove($index, $type, $elastic_id)
    {
        return static::_Remove($index, $type, $elastic_id);
    }


    public static function DeleteIndexType($index, $type)
    {
        return static::_DeleteIndexType($index, $type);
    }

    public static function DeleteIndex($index)
    {
        return static::_DeleteIndex($index);
    }

    public static function CreateIndex($index, $json)
    {
        return static::_CreateIndex($index, $json);
    }

    public static function CreateIndexType($index, $type, $json)
    {
        return static::_CreateIndexType($index, $type, $json);
    }

    public static function SearchQuery($query, $page = 0, $per_page = 20)
    {
        return static::_SearchQuery(static::$_index, static::$_type, $query, $page, $per_page);
    }

    public static function SimpleSearch(
        $where, $page = 0, $per_page = 20, $order_by = null, $fields = null
    )
    {
        return static::_SimpleSearch(static::$_index, static::$_type, $where, $page, $per_page, $order_by, $fields);
    }

    public static function Search(
        $where, $page = 0, $per_page = 20, $order_by = null, $facets = null, $mincount = 0, $fq = null, $fields = null
    )
    {
        return static::_Search(static::$_index, static::$_type, $where, $page, $per_page, $order_by, $facets, $mincount, $fq, $fields);
    }

    public static function Stats($index, $type, $query, $fields = null, $is_numeric = true)
    {
        return static::_Stats($index, $type, $query, $fields, $is_numeric);
    }

    public static function Insert($index, $type, &$json)
    {
        return static::_Insert($index, $type, $json);
    }

    protected static function _DeleteIndexType($index, $type)
    {
        if (!static::_connect()) {
            return null;
        }

        // Set the index and type
        $params['index'] = $index;
        $params['type'] = $type;

        $res = static::$client->indices()->deleteMapping($params);

        return $res;
    }

    protected static function _DeleteIndex($index)
    {
        if (!static::_connect()) {
            return null;
        }
        $params['index'] = $index;

        $res = static::$client->indices()->delete($params);
        return $res;
    }

    protected static function _CreateIndex($index, $json)
    {
        if (!static::_connect()) {
            return null;
        }
        $params['index'] = $index;
        $params['body'] = $json;

        $res = static::$client->indices()->create($params);
        return $res;
    }

    protected static function _DeleteRemoved($index, $type)
    {
        if (!static::_connect()) {
            return null;
        }
        try {

            return static::$client->deleteByQuery(
                [
                    'index' => $index,
                    'type' => $type,
                    'body' => [
                        'query' => [
                            'match' => [
                                'status' => 'removed',
                            ]
                        ]
                    ]
                ]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    protected static function _CreateIndexType($index, $type, $json)
    {
        if (!static::_connect()) {
            return null;
        }

        // Set the index and type
        $params['index'] = $index;
        $params['type'] = $type;

        $params['body'] = $json;
        $res = static::$client->indices()->putMapping($params);

        return $res;
    }

    protected static function _SearchQuery($index, $type, $query, $page = 0, $per_page = 20)
    {
        global $Request;

        if (!static::_connect()) {
            if ($Request->query_log) {
                exit('could not connect');
            }
            return null;
        }

        $start_time = microtime(true);
        Metrics::Start('ELASTIC');

        if (!is_array($query)) {
            $query = json_decode($query, true);
        }

        $query['from'] = $page * $per_page;
        $query['size'] = $per_page;

        $query = json_encode(fix_json($query));
        $params['index'] = $index;
        $params['type'] = $type;
        $params['body'] = $query;

        $array = static::$client->search($params);

        $list = [];
        $list['count'] = $array['hits']['total'];
        $list['qtime'] = $array['took'];
        $list['query'] = $params;
        //$list['error'] = isset($array['error']) ? $array['error']['msg'] . ' (' . $array['error']['code'] .  ')': '';

        $list['data'] = [];
        if (isset($array['hits']['hits'])) {
            foreach ($array['hits']['hits'] as $row) {
                $list['data'][] = $row['_source'];
            }
        }

        Metrics::Stop('ELASTIC');
        self::Log($query, microtime(true) - $start_time);
        return $list;
    }

    protected static function _SimpleSearch(
        $index, $type, $where, $page = 0, $per_page = 20, $order_by = null, $fields = null
    )
    {
        $start_time = microtime(true);
        Metrics::Start('ELASTIC');

        if (is_null($page))
            $page = 0;

        if (is_null($per_page))
            $per_page = 20;

        $params = [];
        $params[] = 'q=' . urlencode(implode(' AND ', $where));
        if ($fields) {
            $params[] = 'fields=' . implode(',', $fields);
        }

        $params[] = 'from=' . ($page * $per_page);
        $params[] = 'size=' . ($per_page);
        $params[] = 'pretty';


        if ($order_by) {
            if (is_array($order_by))
                $order_by = implode(',', $order_by);
            $params[] = 'sort=' . urlencode($order_by);
        }


        $url = rtrim(static::$ACTIVE_ELASTIC_URL, '/\\ ') . '/' . $index . '/' . $type . '/_search/';

        // can't use POST with ElasticSearch
        $res = Curl::Get($url, implode('&', $params));

        $array = @json_decode($res->Body, true, 512, JSON_BIGINT_AS_STRING);

        if (isset($array['error']) && $array['error']) {
            return [];
        }

        if (isset($array['_shards']['failures']) && $array['_shards']['failures']) {
            return [];
        }

        if (!isset($array['hits'])) {
            return [];
        }
        $list = [];
        $list['count'] = $array['hits']['total'];
        $list['qtime'] = $array['took'];
        //$list['error'] = isset($array['error']) ? $array['error']['msg'] . ' (' . $array['error']['code'] .  ')': '';

        $list['data'] = [];
        if (isset($array['hits']['hits'])) {
            foreach ($array['hits']['hits'] as $row) {
                if ($fields) {
                    $t = [];
                    foreach ($row['fields'] as $field => $vals) {
                        $t[$field] = $vals[0];
                    }
                    $list['data'][] = $t;
                } else {
                    $t = $row['_source'];
                    $t['_id'] = $row['_id'];

                    $list['data'][] = $t;
                }
            }
        }
        $list['query'] = implode(' AND ', $where);
        $list['url'] = $url;

        Metrics::Stop('ELASTIC');
        self::Log($list['query'], microtime(true) - $start_time);

        return $list;
    }


    protected static function _Search(
        $index, $type, $where, $page = 0, $per_page = 20, $order_by = null, $facets = null, $mincount = 0, $fq = null, $fields = null
    )
    {
        $start_time = microtime(true);
        Metrics::Start('ELASTIC');

        if (is_null($page))
            $page = 0;

        if (is_null($per_page))
            $per_page = 20;

        $params = [];
        $params[] = 'q=' . urlencode(implode(' AND ', $where));
        if ($fields) {
            $params[] = 'fields=' . implode(',', $fields);
        }

        $params[] = 'from=' . ($page * $per_page);
        $params[] = 'size=' . ($per_page);
        $params[] = 'pretty';

        if ($order_by) {
            if (is_array($order_by))
                $order_by = implode(',', $order_by);
            $params[] = 'sort=' . urlencode($order_by);
        }


        $url = rtrim(static::$ACTIVE_ELASTIC_URL, '/\\ ') . '/' . $index . '/' . $type . '/_search/?' . implode('&', $params);

        $res = Curl::Post($url, null);

        $array = json_decode($res->Body, true);

        $list = [];
        $list['count'] = $array['hits']['total'];
        $list['qtime'] = $array['took'];
        //$list['error'] = isset($array['error']) ? $array['error']['msg'] . ' (' . $array['error']['code'] .  ')': '';

        $list['data'] = [];
        if (isset($array['hits']['hits'])) {
            foreach ($array['hits']['hits'] as $row) {
                if (!$fields) {
                    $t = $row['_source'];
                    $t['_id'] = $row['_id'];
                    $list['data'][] = $t;
                } else {
                    $t = [];
                    $t['_id'] = $row['_id'];
                    foreach ($row['fields'] as $f => $data) {
                        $t[$f] = $data[0];
                    }
                    $list['data'][] = $t;
                }
            }
        }
        $list['query'] = implode(' AND ', $where);
        $list['url'] = $url;

        Metrics::Stop('ELASTIC');
        self::Log($list['query'], microtime(true) - $start_time);

        return $list;
    }

    protected static function _Stats($index, $type, $query, $fields = null, $is_numeric = true)
    {
        if (!static::_connect()) {
            return null;
        }

        Metrics::Start('ELASTIC');
        $query['from'] = 0;
        $query['size'] = 0;

        $params['index'] = $index;
        $params['type'] = $type;
        $params['body'] = $query;

        foreach ($fields as $field) {
            if ($is_numeric) {
                $params['body']['aggs'][$field . '_max']
                    = ['max' => ['field' => $field]];
                $params['body']['aggs'][$field . '_min']
                    = ['min' => ['field' => $field]];
            } else {
                $params['body']['aggs'][$field]
                    = ['terms' => ['field' => $field]];
            }
        }
        $array = static::$client->search($params);
        $res = $array['aggregations'];
        Metrics::Stop('ELASTIC');
        return $res;
    }

    /**
     * @param $json
     *
     * @return mixed|null
     */
    protected static function _InsertUpdate($index, $type, &$json)
    {
        if (!static::_connect()) {
            return null;
        }
        Metrics::Start('ELASTIC');

        // this is necessary to fix any UTF-8 encoding errors from the database
        $json = fix_json($json);

        $params = [];
        $params['index'] = $index;
        $params['type'] = $type;

        foreach ($json as $key => $el) {
            if (substr($key, 0, strlen('md5::')) === 'md5::') {
                $key = str_replace('md5::', '', $key);
                $params['body'][] = [
                    'index' => [
                        '_id' => $key
                    ]
                ];
            } else {
                $params['body'][] = [
                    'index' => [
                        '_id' => md5($key)
                    ]
                ];
            }
            $params['body'][] = $el;
        }
        $res = static::$client->bulk($params);
        Metrics::Stop('ELASTIC');

        return $res;
    }

    protected static function _Insert($index, $type, &$json)
    { // single inserts only, use insertupdate for bulk inserts
        if (!static::_connect()) {
            return null;
        }
        Metrics::Start('ELASTIC');

        // this is necessary to fix any UTF-8 encoding errors from the database
        $json = fix_json($json);

        $params = [];
        $params['body'][]['index'] = ['_index' => $index, '_type' => $type];

        foreach ($json as $key => $el) {
            $params['body'][] = $el;
        }
        $res = static::$client->bulk($params);
        Metrics::Stop('ELASTIC');

        return $res;
    }

    /**
     * @param $elastic_id
     *
     * @return mixed|null
     */
    protected static function _Remove($index, $type, $elastic_id)
    {
        if (!static::_connect()) {
            return null;
        }
        return null;
    }

    public static function Execute($path, $command, $json)
    {
        if (!static::_connect()) {
            return null;
        }
        Metrics::Start('ELASTIC');

        // this is necessary to fix any UTF-8 encoding errors from the database
        $json = fix_json($json);
        $res = null;
        switch ($path) {
            case '_cluster':
                switch ($command) {
                    case 'reroute':
                        $res = static::$client->cluster()->reroute($json);
                        break;
                    default:
                        Halt('unknown command ' . $command);
                }
                break;
            default:
                Halt('unknown path ' . $path);
        }
        Metrics::Stop('ELASTIC');

        return $res;
    }

    public static function GetIndexes()
    {
        $url = static::$ACTIVE_ELASTIC_URL . '/_aliases?pretty';
        $res = Curl::Get($url, null);
        return json_decode($res->Body, true);
    }

    public static function GetMappings($index) {
        $url = static::$ACTIVE_ELASTIC_URL . '/' . $index . '/_mapping?pretty';
        $res = Curl::Get($url, null);
        return json_decode($res->Body, true);
    }
}