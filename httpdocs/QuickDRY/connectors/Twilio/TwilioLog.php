<?php
namespace QuickDRY\Connectors;


use QuickDRY\Utilities\SafeClass;

class TwilioLog extends SafeClass
{

    public $phone_number;
    public $method;
    public $is_success;
    public $params;
    public $response;
    public $twilio_mode_id;
    public $created_at;
    public $sid;
    public $date_created;
    public $date_updated;
    public $date_sent;
    public $account_sid;
    public $to;
    public $from;
    public $body;
    public $status;
    public $direction;
    public $api_version;
    public $price;
    public $price_unit;
    public $uri;
    public $num_segments;

    public function Save()
    {

    }
}