<?php
namespace QuickDRY\Connectors;

use Elasticsearch\ClientBuilder;
use QuickDRY\Utilities\Debug;
use QuickDRY\Utilities\Metrics;
use QuickDRY\Utilities\Strings;
use QuickDRY\Web\Web;

/**
 * Class Elastic_Core
 */
class Elastic_Core extends Elastic_Base
{
  // http://domain:9200/_cat/indices?v&pretty
  // http://domain:9200/index/type/_search?q=*:*&pretty

  /* @var $client Elasticsearch\Client */
  protected static $client = null;

  public static $use_log = false;
  public static $log = [];
  public static $query_count = 0;
  public static $query_time = 0;

  private static function LogQuery($query, $time)
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
      Debug::Halt('QuickDRY Error: $ACTIVE_ELASTIC_URL is not set');
      return null;
    }

    if (is_null(static::$client)) {
      static::$client = ClientBuilder::create()->setHosts([static::$ACTIVE_ELASTIC_URL])->build();
    }

    if (!static::$client) {
      Debug::Halt('QuickDRY Error: Could Not Connect to Elastic Search Server ' . static::$ACTIVE_ELASTIC_URL);
    }

    return static::$client;
  }

  public function Save()
  {
    if (!static::$_index) {
      Debug::Halt('QuickDRY Error: static::$_index not defined');
    }

    if (!$this->_id) {
      $vars = [$this->ToArray(true)];
      $res = static::_Insert(static::$_index, static::$_type, $vars);
      if (!isset($res['items'][0]['index']['_id'])) {
        Debug::Halt($res['items']);
      }
      $this->_id = $res['items'][0]['index']['_id'];
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
   * @return array|null
   */
  public static function InsertUpdate(&$json): ?array
  {
    return static::_InsertUpdate(static::$_index, static::$_type, $json);
  }

  public static function DeleteIndexType($index, $type)
  {
    return static::_DeleteIndexType($index, $type);
  }

  public static function DeleteIndex($index)
  {
    return static::_DeleteIndex($index);
  }

  public static function Delete($params)
  {
    return static::_Delete(static::$_index, static::$_type, $params);
  }

  public static function Truncate()
  {
    return static::_Truncate(static::$_index, static::$_type);
  }

  public static function CreateIndex($index, $json)
  {
    return static::_CreateIndex($index, $json);
  }

  public static function UpdateIndex($index, $json)
  {
    return static::_UpdateIndex($index, $json);
  }

  public static function CreateIndexType($index, $type, $json)
  {
    return static::_CreateIndexType($index, $type, $json);
  }

  public static function SearchQuery($query, $page = 0, $per_page = 20)
  {
    return static::_SearchQuery(static::$_index, static::$_type, $query, $page, $per_page);
  }

  public static function Aggregation($query)
  {
    return static::_Aggregation(static::$_index, static::$_type, $query);
  }

  public static function ScrollIndexType($index, $type, $where, $map_function = null)
  {
    return static::_ScrollIndexType($index, $type, $where, $map_function);
  }

  public static function SearchInIndexType(
    $index, $type, $where, $page = 0, $per_page = 20, $order_by = null, $fields = null
  )
  {
    return static::_Search($index, $type, $where, $page, $per_page, $order_by, $fields);
  }

  public static function Search(
    $where, $page = 0, $per_page = 20, $order_by = null, $fields = null
  )
  {
    return static::_Search(static::$_index, static::$_type, $where, $page, $per_page, $order_by, $fields);
  }

  public static function Stats($index, $type, $query, $fields = null, $is_numeric = true)
  {
    return static::_Stats($index, $type, $query, $fields, $is_numeric);
  }

  public static function Insert($index, $type, &$json)
  {
    $res = static::_Insert($index, $type, $json);
    if (isset($res['items'][0]['index']['error'])) {
      return ['error' => $res['items'][0]['index']['error']];
    }
    return [
      'last_id' => $res['items'][0]['index']['_id'],
      'error' => ''
    ];
  }

  public static function GetAllForIndexType($index, $type, $where, $limit = 10000, $map_function = null)
  {
    $res = self::SearchInIndexType($index, $type, $where, 0, 0);
    $count = $res['count'];

    if (!$count) {
      return [];
    }
    $list = [];

    if (!$limit && $count > 10000) {
      $res = self::ScrollIndexType($index, $type, $where, $map_function);
      if (isset($res['data'])) {
        foreach ($res['data'] as $row) {
          $list[] = $row;
        }
      }
      return $list;
    }


    $count = $limit ? ($count < $limit ? $count : $limit) : $count;
    $page = 0;
    $per_page = $limit ?: 10000; // arbitrary limit
    $max_page = ceil($count / $per_page);

    while ($page < $max_page) {
      // Log::Insert([$page, $max_page], true);
      $res = self::SearchInIndexType($index, $type, $where, $page, $per_page);
      foreach ($res['data'] as $row) {
        $list[] = $row;
      }
      $page++;
      // Log::Insert($page . ': ' . sizeof($res['data']), true);
    }
    return $list;
  }

  protected static function _DeleteIndexType($index, $type)
  {
    if (!static::_connect()) {
      return null;
    }

    // Set the index and type
    $params['index'] = $index;
    $params['type'] = $type;

    return static::$client->indices()->deleteMapping($params);
  }

  protected static function _DeleteIndex($index)
  {
    if (!static::_connect()) {
      return null;
    }
    $params['index'] = $index;

    return static::$client->indices()->delete($params);
  }

  protected static function _CreateIndex($index, $json)
  {
    if (!static::_connect()) {
      return null;
    }
    $params['index'] = $index;
    $params['body'] = $json;

    return static::$client->indices()->create($params);
  }

  protected static function _UpdateIndex($index, $json)
  {
    if (!static::_connect()) {
      return null;
    }
    $params['index'] = $index;
    $params['body'] = $json;

    return static::$client->indices()->putSettings($params);
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
    return static::$client->indices()->putMapping($params);
  }

  protected static function _Aggregation($index, $type, $query)
  {
    global $Web;
    if (!static::_connect()) {
      if ($Web && $Web->Request->query_log) {
        exit('could not connect');
      }
      return null;
    }

    if (!is_array($query)) {
      $query = json_decode($query, true);
    }
    $query['size'] = 0;

    $query = json_encode(Strings::FixJSON($query));
    $params['index'] = $index;
    $params['type'] = $type;
    $params['body'] = $query;

    $array = static::$client->search($params);

    return $array['aggregations'] ?? [];
  }

  /**
   * @param $index
   * @param $type
   * @param $query
   * @param int $page
   * @param int $per_page
   * @return array
   */
  protected static function _SearchQuery($index, $type, $query, $page = 0, $per_page = 20): array
  {
    if (!static::_connect()) {
      Debug::Halt('QuickDRY Error: Could Not Connect to Elastic Search Server');
    }

    $start_time = microtime(true);
    Metrics::Start('ELASTIC::_SearchQuery');

    if (!is_array($query)) {
      $query = json_decode($query, true);
    }

    $query['from'] = $page * $per_page;
    $query['size'] = $per_page;

    $query = json_encode(Strings::FixJSON($query));
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

    Metrics::Stop('ELASTIC::_SearchQuery');
    self::LogQuery($query, microtime(true) - $start_time);
    return $list;
  }

  private static function _ScrollIndexTypeScrollID($index, $type, $where, $ScrollID, &$list, $map_function = null)
  {
    if ($where && sizeof($where)) {
      $params = [];
      $params[] = 'q=' . urlencode(implode(' AND ', $where));
    }
    $params[] = 'pretty';
    if ($ScrollID) {
      $params[] = 'scroll_id=' . $ScrollID;
      $url = rtrim(static::$ACTIVE_ELASTIC_URL, '/\\ ') . '/_search/scroll?scroll=5m&' . implode('&', $params);
    } else {
      $params[] = 'size=10000';
      $url = rtrim(static::$ACTIVE_ELASTIC_URL, '/\\ ') . '/' . $index . '/' . $type . '/_search/?scroll=5m&' . implode('&', $params);

    }


    $res = Curl::Post($url, null);

    $array = json_decode($res->Body, true);

    $list['count'] = $array['hits']['total'] ?? 0;
    $list['qtime'] = $array['took'] ?? 0;
    //$list['error'] = isset($array['error']) ? $array['error']['msg'] . ' (' . $array['error']['code'] .  ')': '';

    if (isset($array['hits']['hits']) && sizeof($array['hits']['hits'])) {
      foreach ($array['hits']['hits'] as $row) {
        $t = $row['_source'];
        $t['_id'] = $row['_id'];
        if ($map_function) {
          call_user_func($map_function, $t);
        } else {
          $list['data'][] = $t;
        }
      }
    } else {
      return null;
    }

    if ($where && sizeof($where)) {
      $list['query'] = implode(' AND ', $where);
    } else {
      $list['query'] = '';
    }
    $list['url'] = $url;

    return $array['_scroll_id'] ?? null;
  }

  protected static function _ScrollIndexType($index, $type, $where, $map_function = null)
  {

    $list = [];
    $list['data'] = [];
    $scroll_id = null;
    $scroll_id = self::_ScrollIndexTypeScrollID($index, $type, $where, $scroll_id, $list, $map_function);
    while ($scroll_id) {
      $scroll_id = self::_ScrollIndexTypeScrollID($index, $type, $where, $scroll_id, $list, $map_function);
    }
    return $list;
  }

  protected static function _Search(
    $index, $type, $where, $page = 0, $per_page = 20, $order_by = null, $fields = null
  )
  {
    $start_time = microtime(true);
    Metrics::Start('ELASTIC::_Search');

    if (is_null($page)) {
      $page = 0;
    }

    if (is_null($per_page)) {
      $per_page = 20;
    }

    if ($where && sizeof($where)) {
      $params = [];
      $params[] = 'q=' . urlencode(implode(' AND ', $where));
      if ($fields) {
        $params[] = 'fields=' . implode(',', $fields);
      }
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
    $list['count'] = $array['hits']['total'] ?? 0;
    $list['qtime'] = $array['took'] ?? 0;
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
    if ($where && sizeof($where)) {
      $list['query'] = implode(' AND ', $where);
    } else {
      $list['query'] = '';
    }
    $list['url'] = $url;

    Metrics::Stop('ELASTIC::_Search');
    self::LogQuery($list['query'], microtime(true) - $start_time);

    return $list;
  }

  /**
   * @param $index
   * @param $type
   * @param $query
   * @param null $fields
   * @param bool $is_numeric
   * @return null
   */
  protected static function _Stats($index, $type, $query, $fields = null, $is_numeric = true)
  {
    if (!static::_connect()) {
      return null;
    }

    Metrics::Start('ELASTIC::_Stats');
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
    Metrics::Stop('ELASTIC::_Stats');
    return $res;
  }

  /**
   * @param $index
   * @param $type
   * @param $json
   * @return array|null
   */
  protected static function _InsertUpdate($index, $type, &$json)
  {
    if (!static::_connect()) {
      return null;
    }
    Metrics::Start('ELASTIC::_InsertUpdate');

    // this is necessary to fix any UTF-8 encoding errors from the database
    $json = Strings::FixJSON($json);

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
    Metrics::Stop('ELASTIC::_InsertUpdate');

    if (isset($res['errors']) && $res['errors']) {
      $res['error_list'] = [];
      foreach ($res['items'] as $i => $row) {
        if (isset($row['index']['error'])) {
          $res['error_list'] = $row;
        }
      }
      unset($res['items']);
    }
    return $res;
  }

  /**
   * @param $index
   * @param $type
   * @param $json
   * @return array|null
   */
  protected static function _Insert($index, $type, &$json)
  { // single inserts only, use insertupdate for bulk inserts
    if (!static::_connect()) {
      return null;
    }
    Metrics::Start('ELASTIC::_Insert');

    // this is necessary to fix any UTF-8 encoding errors from the database
    $json = Strings::FixJSON($json);

    $params = [];
    $params['body'][]['index'] = ['_index' => $index, '_type' => $type];

    foreach ($json as $key => $el) {
      $params['body'][] = $el;
    }

    $res = static::$client->bulk($params);
    Metrics::Stop('ELASTIC::_Insert');

    return $res;
  }

  /**
   * @param $index
   * @param $type
   * @param $params
   * @return mixed|null
   */
  protected static function _Delete($index, $type, $params)
  {
    if (!static::_connect()) {
      return null;
    }

    $query = [
      'index' => $index,
      'type' => $type,
      'body' => [
        'query' => [
          'match' => $params
        ]
      ]
    ];

    return static::$client->deleteByQuery($query);
  }

  /**
   * @param $index
   * @param $type
   * @return array|null
   */
  protected static function _Truncate($index, $type): ?array
  {
    if (!static::_connect()) {
      return null;
    }

    // (object) is required for match_all to turn the [] into {} in JSON as expected
    $query = [
      'index' => $index,
      'type' => $type,
      'body' => [
        'query' => [
          'match_all' => (object)[]
        ]
      ]
    ];

    return static::$client->deleteByQuery($query);
  }

  /**
   * @param $path
   * @param $command
   * @param $json
   * @return array|null
   */
  public static function Execute($path, $command, $json): ?array
  {
    if (!static::_connect()) {
      return null;
    }
    Metrics::Start('ELASTIC::Execute');

    // this is necessary to fix any UTF-8 encoding errors from the database
    $json = Strings::FixJSON($json);
    $res = null;
    switch ($path) {
      case '_cluster':
        switch ($command) {
          case 'reroute':
            $res = static::$client->cluster()->reroute($json);
            break;
          default:
            Debug::Halt('QuickDRY Error: unknown command ' . $command);
        }
        break;
      default:
        Debug::Halt('QuickDRY Error: unknown path ' . $path);
    }
    Metrics::Stop('ELASTIC::Execute');

    return $res;
  }

  public static function GetIndexes()
  {
    $url = static::$ACTIVE_ELASTIC_URL . '/_aliases?pretty';
    $res = Curl::Get($url);
    return json_decode($res->Body, true);
  }

  public static function GetMappings($index)
  {
    $url = static::$ACTIVE_ELASTIC_URL . '/' . $index . '/_mapping?pretty';
    $res = Curl::Get($url);
    return json_decode($res->Body, true);
  }
}