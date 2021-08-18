<?php
namespace QuickDRY\Connectors;


use QuickDRY\Utilities\SafeClass;

class TwilioLog extends SafeClass
{

    public ?string $phone_number = null;
    public ?string $method = null;
    public ?string $is_success = null;
    public ?string $params = null;
    public ?string $response = null;
    public ?string $twilio_mode_id = null;
    public ?string $created_at = null;
    public ?string $sid = null;
    public ?string $date_created = null;
    public ?string $date_updated = null;
    public ?string $date_sent = null;
    public ?string $account_sid = null;
    public ?string $to = null;
    public ?string $from = null;
    public ?string $body = null;
    public ?string $status = null;
    public ?string $direction = null;
    public ?string $api_version = null;
    public ?string $price = null;
    public ?string $price_unit = null;
    public ?string $uri = null;
    public ?string $num_segments = null;

    public function Save()
    {

    }
}