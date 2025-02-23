<?php

namespace Ivy;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private PHPMailer $mailer;

    function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->SMTPDebug = false; //Enable verbose debug output SMTP::DEBUG_SERVER
        $this->mailer->isSMTP(); //Send using SMTP
        $this->mailer->Host = $_ENV['MAIL_HOST']; //Set the SMTP server to send through
        $this->mailer->SMTPAuth = true; //Enable SMTP authentication
        $this->mailer->Username = $_ENV['MAIL_USERNAME']; //SMTP username
        $this->mailer->Password = $_ENV['MAIL_PASSWORD']; //SMTP password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //`PHPMailer::ENCRYPTION_STARTTLS` Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $this->mailer->Port = $_ENV['MAIL_PORT'];

        $this->mailer->setFrom($_ENV['MAIL_SENDER_ADDRESS'], $_ENV['MAIL_SENDER_NAME']);
        $this->mailer->addReplyTo($_ENV['MAIL_SENDER_ADDRESS'], $_ENV['MAIL_SENDER_NAME']);
    }

    function send(): string
    {
        try {
            $this->mailer->send();
            return Language::get('mail.send.succesfully');
        } catch (Exception) {
            return Language::get('mail.send.unsuccesfully') . $this->mailer->ErrorInfo;
        }

    }

    public function addAddress(string $address, string $name): Mail
    {
        $this->mailer->addAddress($this->address, $this->name);

        return $this;
    }

    public function setSubject(string $subject): Mail
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    public function setBody(string $body): Mail
    {
        $this->mailer->Body = $body;

        return $this;

    }

    public function setAltBody(string $altBody): Mail
    {
        $this->mailer->AltBody = $altBody;

        return $this;
    }

    public function isHTML(?bool $bool = null): Mail
    {
        $this->mailer->isHTML($bool);

        return $this;
    }
}
