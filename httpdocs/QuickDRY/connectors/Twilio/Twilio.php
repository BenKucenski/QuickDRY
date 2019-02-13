<?php
Define('TWILIO_MODE_TEST', 0);
Define('TWILIO_MODE_LIVE', 1);

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

        switch(get_class($response)) {
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
            default:
                CleanHalt($response);

        }

        $tl->Save();
    }

    public static function SetMode($mode)
    {
        switch($mode) {
            case TWILIO_MODE_LIVE:
                self::$sid = TWILIO_SID;
                self::$token = TWILIO_TOKEN;
                self::$from_number = TWILIO_FROM_NUMBER;
                break;
            case TWILIO_MODE_TEST:
                self::$sid = TWILIO_TEST_SID;
                self::$token = TWILIO_TEST_TOKEN;
                self::$from_number = TWILIO_FROM_TEST_NUMBER;
                break;
            default:
                Halt('QuickDRY Error: invalid twilio mode');
        }
        self::$mode = $mode;
    }



    public static function SendSMS($mobile_number, $text, $allow = false)
    {
        if($allow || TwilioDNC::CheckNumber($mobile_number)) {

            $client = new Services_Twilio(self::$sid, self::$token);

            try {
                /* @var $res Services_Twilio_Rest_SmsMessage */
                $res = $client->account->sms_messages->create(
                    self::$from_number, $mobile_number, $text
                );
                $res->client->ClearCreds();

                self::Log($mobile_number, 'SendSMS', [$mobile_number, $text], $res, true);

                return $res->sid;
            } catch (Exception $e) {
                self::Log($mobile_number, 'SendSMS', [$mobile_number, $text], $e, false);
            }
            return true;
        }
        return false;
    }

    public static function TestTwilio()
    {
        $client = new Services_Twilio(self::$sid, self::$token);

        try {
            $number = $client->account->incoming_phone_numbers->create(array(
                "VoiceUrl" => "http://demo.twilio.com/docs/voice.xml",
                "PhoneNumber" => "+15005550006"
            ));
            return $number->sid;
        } catch(Exception $e) {
            Halt($e);
        }
        return null;
    }
}