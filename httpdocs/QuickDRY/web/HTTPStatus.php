<?php
define('HTTP_STATUS_OK', 200);
define('HTTP_STATUS_NOT_MODIFIED', 304);
define('HTTP_STATUS_BAD_REQUEST', 400);
define('HTTP_STATUS_UNAUTHORIZED', 401);
define('HTTP_STATUS_FORBIDDEN', 403);
define('HTTP_STATUS_NOT_FOUND', 404);
define('HTTP_STATUS_NOT_ACCEPTABLE', 406);
define('HTTP_STATUS_GONE', 410);
define('HTTP_STATUS_CALM_DOWN', 420);
define('HTTP_STATUS_UNPROCESSABLE_ENTITY', 422);
define('HTTP_STATUS_TOO_MANY_REQUESTS', 429);
define('HTTP_STATUS_INTERNAL_SERVER_ERROR', 500);
define('HTTP_STATUS_BAD_GATEWAY', 502);
define('HTTP_STATUS_SERVICE_UNAVAILABLE', 503);
define('HTTP_STATUS_GATEWAY_TIMEOUT', 504);

class HTTPStatus
{
    public static function GetDescription($http_status_code)
    {
        if(!is_numeric($http_status_code)) {
            Halt('Invalid status code: ' . $http_status_code);
        }

        switch($http_status_code)
        {
            case HTTP_STATUS_OK: return 'OK';
            case HTTP_STATUS_NOT_MODIFIED: return 'Not Modified';
            case HTTP_STATUS_BAD_REQUEST: return 'Bad Request';
            case HTTP_STATUS_UNAUTHORIZED: return 'Unauthorized';
            case HTTP_STATUS_FORBIDDEN: return 'Forbidden';
            case HTTP_STATUS_NOT_FOUND: return 'Not Found';
            case HTTP_STATUS_NOT_ACCEPTABLE: return 'Not Acceptable';
            case HTTP_STATUS_GONE: return 'Gone';
            case HTTP_STATUS_CALM_DOWN: return 'Calm Your Scripts';
            case HTTP_STATUS_UNPROCESSABLE_ENTITY: return 'Unprocessable Entity';
            case HTTP_STATUS_TOO_MANY_REQUESTS: return 'Too Many Requests';
            case HTTP_STATUS_INTERNAL_SERVER_ERROR: return 'Internal Server Error';
            case HTTP_STATUS_BAD_GATEWAY: return 'Bad Gateway';
            case HTTP_STATUS_SERVICE_UNAVAILABLE: return 'Service Unavailable';
            case HTTP_STATUS_GATEWAY_TIMEOUT: return 'Gateway timeout';

        }

        Halt('Invalid status code: ' . $http_status_code);
        return null;
    }
}