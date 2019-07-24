<?php

/**
 * Class Mailer
 */
class Mailer extends SafeClass
{
    public $message;
    public $subject;
    public $to_email;
    public $to_name;
    public $is_sent;
    public $sent_at;
    public $log;
    public $headers;
    public $from_email;
    public $from_name;

    /**
     * @param $name
     * @return array|int|mixed|null
     */
    public function __get($name)
    {
        return parent::__get($name);
    }


    /**
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param null $attachments
     * @param integer $to_user_id
     * @param string $log
     * @param integer $entity_id
     * @param integer $entity_type_id
     * @return Mailer
     *
     */


    public static function Queue($to_email, $to_name, $subject, $message, $attachments = null, $from_email = null, $from_name = null)
    {
        $t = new self();
        $t->to_email = $to_email;
        $t->to_name = $to_name;
        $t->from_email = $from_email;
        $t->from_name = $from_name;
        $t->subject = $subject;
        $t->message = $message;
        $t->headers = serialize($attachments);

        return $t;
    }

    /**
     * @param bool $debug
     * @return int
     */
    public function Send($debug = false)
    {

        if (defined('SMTP_ON')) {
            if (SMTP_ON == 0) {
                return -1;
            }
        }

        if (!defined('SMTP_FROM_EMAIL') || !defined('SMTP_FROM_NAME')) {
            exit('SMTP_FROM_EMAIL or SMTP_FROM_NAME not defined');
        }

        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            if (defined('SMTP_DEBUG_EMAIL')) {
                $this->to_email = SMTP_DEBUG_EMAIL;
            } else {
                return -2;
            }
        }

        $to_emails = explode(',', str_replace(';',',', $this->to_email));
        foreach($to_emails as $to) {

            $mail = new PHPMailer();

            $mail->Host = SMTP_HOST;
            $mail->From = $this->from_email ? $this->from_email : SMTP_FROM_EMAIL;
            $mail->FromName = $this->from_name ? $this->from_name : SMTP_FROM_NAME;
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 25;

            if (defined('SMTP_USER') && defined('SMTP_PASS')) {
                if (SMTP_USER && SMTP_PASS) {
                    $mail->Password = SMTP_PASS;
                    $mail->Username = SMTP_USER;
                    $mail->AuthType = SMTP_AUTH;
                    $mail->SMTPAuth = true;
                    if(SMTP_AUTH !== 'PLAIN') {
                        $mail->SMTPSecure = 'tls';
                    }
                }
            }
            $mail->Mailer = 'smtp';


            $mail->AddAddress($to, $this->to_name);
            $mail->Subject = $this->subject;
            $mail->MsgHTML($this->message);

            $attachments = unserialize($this->headers);
            if (!is_null($attachments) && is_array($attachments)) {
                foreach ($attachments as $name => $path) {
                    if (!file_exists($path)) {
                        $path = '../' . $path;
                    }
                    if (!file_exists($path)) {
                        Log::Insert(['error' => 'invalid attachment', $name => $path]);
                        continue;
                    }
                    try {
                        $mail->AddAttachment($path, $name);
                    } catch (Exception $ex) {
                        $this->log = $ex->getMessage();
                        return 0;
                    }
                }
            }

            try {
                if (!$mail->Send()) {
                    if ($debug) {
                        Halt([$mail->ErrorInfo, $mail]);
                    }
                    $this->log = $mail->ErrorInfo;
                    return 0;
                }
            } catch(Exception $e) {
                $this->log = $e->getMessage();
                return 0;
            }
        }
        $this->is_sent = true;
        $this->sent_at = Dates::Timestamp(time());

        return 1;
    }

    public static function Template($filename, $values)
    {
        if(!file_exists($filename)) {
            Halt(['error'=>'File does not exist', $filename]);
        }
        $html = file_get_contents($filename);
        foreach($values as $key => $value) {
            $html = str_ireplace('##' . $key . '##',$value, $html);
        }
        $matches = [];
        preg_match_all('/\#\#(.*?)\#\#/si', $html, $matches);
        if(sizeof($matches[1])) {
            CleanHalt(['Error'=>'HTML still contains variables', $matches[1], $html]);
        }
        return $html;
    }
}