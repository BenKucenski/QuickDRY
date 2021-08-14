<?php

namespace QuickDRY\Utilities;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Mailer
 */
class Mailer extends SafeClass
{
  public string $message;
  public string $subject;
  public string $to_email;
  public string $to_name;
  public bool $is_sent;
  public $sent_at;
  public string $log;
  public string $headers;
  public ?string $from_email;
  public ?string $from_name;

  public $mail;

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
   * @param string $to_email
   * @param string $to_name
   * @param string $subject
   * @param string $message
   * @param array|null $attachments
   * @param string|null $from_email
   * @param string|null $from_name
   * @return Mailer
   */


  public static function Queue(string $to_email, string $to_name,
                               string $subject, string $message,
                               array $attachments = null,
                               string $from_email = null, string $from_name = null): Mailer
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
   * @param bool $smtp_output
   * @return int
   * @throws \PHPMailer\PHPMailer\Exception
   */
  public function Send(bool $debug = false, bool $smtp_output = false)
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
        $this->subject = 'TEST EMAIL: ' . $this->subject;
      } else {
        return -2;
      }
    }

    $to_emails = explode(',', str_replace(';', ',', $this->to_email));
    foreach ($to_emails as $to) {

      $mail = new PHPMailer();

      $mail->Host = SMTP_HOST;
      $mail->From = $this->from_email ? $this->from_email : SMTP_FROM_EMAIL;
      $mail->FromName = $this->from_name ? $this->from_name : SMTP_FROM_NAME;
      $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 25;

      $this->from_email = $mail->From;
      $this->from_name = $mail->FromName;
      $mail->SMTPDebug = $smtp_output;

      if (defined('SMTP_USER') && defined('SMTP_PASS')) {
        if (SMTP_USER && SMTP_PASS) {
          $mail->Password = SMTP_PASS;
          $mail->Username = SMTP_USER;
          $mail->AuthType = SMTP_AUTH;
          $mail->SMTPAuth = true;
          $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : '';
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
            Debug::Halt(['error' => 'Missing Attachment File', $this]);
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
            Debug::Halt([$mail->ErrorInfo, $mail]);
          }
          $this->log = $mail->ErrorInfo;
          $this->mail = $mail;
          return 0;
        }
      } catch (Exception $e) {
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
    if (!file_exists($filename)) {
      Debug::Halt(['error' => 'File does not exist', $filename]);
    }
    $html = file_get_contents($filename);
    foreach ($values as $key => $value) {
      $html = str_ireplace('##' . $key . '##', $value, $html);
    }
    $matches = [];
    preg_match_all('/\#\#(.*?)\#\#/si', $html, $matches);
    if (sizeof($matches[1])) {
      Debug::Halt(['Error' => 'HTML still contains variables', $matches[1], $html]);
    }
    return $html;
  }
}