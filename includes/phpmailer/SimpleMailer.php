<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    private $to = [];
    private $from = '';
    private $fromName = '';
    private $replyTo = '';
    private $subject = '';
    private $body = '';
    private $altBody = '';
    private $isHTML = false;
    private $smtpHost = '';
    private $smtpPort = 587;
    private $smtpUsername = '';
    private $smtpPassword = '';
    private $smtpSecure = 'tls';
    public $CharSet = 'UTF-8';
    public $ErrorInfo = '';

    public function __construct($exceptions = false) {
        // Constructor
    }

    public function isSMTP() {
        // SMTP mode
    }

    public function setFrom($email, $name = '') {
        $this->from = $email;
        $this->fromName = $name;
    }

    public function addAddress($email) {
        $this->to[] = $email;
    }

    public function addReplyTo($email, $name = '') {
        $this->replyTo = $email;
    }

    public function isHTML($bool) {
        $this->isHTML = $bool;
    }

    public function __set($name, $value) {
        $property = strtolower($name);
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    public function send() {
        try {
            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: " . ($this->isHTML ? "text/html" : "text/plain") . "; charset={$this->CharSet}";
            $headers[] = "From: {$this->fromName} <{$this->from}>";

            if ($this->replyTo) {
                $headers[] = "Reply-To: {$this->replyTo}";
            }

            $body = $this->isHTML ? $this->body : $this->altBody;

            foreach ($this->to as $recipient) {
                $success = mail($recipient, $this->subject, $body, implode("\r\n", $headers));
                if (!$success) {
                    $this->ErrorInfo = "Failed to send email to {$recipient}";
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }
}

class Exception extends \Exception {}
class SMTP {}
