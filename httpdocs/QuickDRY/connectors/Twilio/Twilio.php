<?php

use Twilio\Rest\Client;

define('TWILIO_MODE_TEST', 0);
define('TWILIO_MODE_LIVE', 1);

class Twilio extends SafeClass
{
    private static $mode = TWILIO_MODE_LIVE;
    private static $sid = TWILIO_SID;
    private static $token = TWILIO_TOKEN;
    private static $from_number = TWILIO_FROM_NUMBER;

    private static function Log($phone_number, $method, $params, $response, $success)
    {
        $tl = new TwilioLog();
        $tl->phone_number = $phone_number;
        $tl->method = $method;
        $tl->is_success = $success;
        $tl->params = json_encode($params);
        $tl->response = serialize($response);
        $tl->twilio_mode_id = self::$mode;
        $tl->created_at = Dates::Timestamp();

        switch (get_class($response)) {
            case 'Services_Twilio_Rest_SmsMessage':
                /* @var $response Services_Twilio_Rest_SmsMessage */
                $tl->sid = $response->sid;
                $tl->date_created = $response->date_created;
                $tl->date_updated = $response->date_updated;
                $tl->date_sent = $response->date_sent;
                $tl->account_sid = $response->account_sid;
                $tl->to = $response->to;
                $tl->from = $response->from;
                $tl->body = $response->body;
                $tl->status = $response->status;
                $tl->direction = $response->direction;
                $tl->api_version = $response->api_version;
                $tl->price = $response->price;
                $tl->price_unit = $response->price_unit;
                $tl->uri = $response->uri;
                $tl->num_segments = $response->num_segments;
                break;
            case 'Services_Twilio_RestException':
                /* @var $response Services_Twilio_RestException */
                $tl->response = $response->getMessage();
                break;
            case 'Twilio\Rest\Api\V2010\Account\CallInstance':
                break;
            default:
                CleanHalt($response);

        }

        return $tl;
    }

    public static function SetMode($mode)
    {
        switch ($mode) {
            case TWILIO_MODE_LIVE:
                self::$sid = TWILIO_SID;
                self::$token = TWILIO_TOKEN;
                self::$from_number = TWILIO_FROM_NUMBER;
                break;
            case TWILIO_MODE_TEST:
                self::$sid = TWILIO_TEST_SID;
                self::$token = TWILIO_TEST_TOKEN;
                self::$from_number = TWILIO_TEST_FROM_NUMBER;
                break;
            default:
                Halt('QuickDRY Error: invalid twilio mode');
        }
        self::$mode = $mode;
    }


    /**
     * @param $mobile_number
     * @param $text
     * @param bool $allow
     * @return TwilioLog|null
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    public static function SendSMS($mobile_number, $text, $allow = false)
    {
        if (!$allow && !TwilioDNC::CheckNumber($mobile_number)) {
            return null;
        }

        try {
            $client = new Client(self::$sid, self::$token);

            /* @var $res Services_Twilio_Rest_SmsMessage */
            $res = $client->account->sms_messages->create(
                self::$from_number, $mobile_number, $text
            );
            $res->client->ClearCreds();

            return self::Log($mobile_number, 'SendSMS', [$mobile_number, $text], $res, true);

        } catch (Exception $e) {
            return self::Log($mobile_number, 'SendSMS', [$mobile_number, $text], $e, false);
        }
    }

    /**
     * @param $to_number
     * @param $xml_url
     * @return TwilioLog
     */
    public static function CallNumber($to_number, $xml_url, $allow = false)
    {
        if (!$allow && !TwilioDNC::CheckNumber($to_number)) {
            return null;
        }

        try {
            //  demo url: http://demo.twilio.com/docs/voice.xml
            $client = new Client(self::$sid, self::$token);
            $res = $client->account->calls->create(
                $to_number,
                self::$from_number,
                [
                    "url" => $xml_url
                ]
            );
            return self::Log($to_number, 'CallNumber', [$to_number, $xml_url], $res, true);
        } catch (Exception $e) {
            return self::Log($to_number, 'CallNumber', [$to_number, $xml_url], $e, false);
        }
    }
}