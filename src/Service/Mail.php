<?php

namespace Ivy\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private PHPMailer $mailer;

    function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['MAIL_HOST'];
        $this->mailer->Port = $_ENV['MAIL_PORT'];
        $this->mailer->SMTPAuth = $_ENV['MAIL_SMTP_AUTH'] === "false" ? false : true;
        if($_ENV['MAIL_SMTP_SECURE'] === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif($_ENV['MAIL_SMTP_SECURE'] === 'tls') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $this->mailer->SMTPSecure = false;
        }
        $this->mailer->SMTPDebug = (int) $_ENV['MAIL_DEBUG'];
        $this->mailer->Username = $_ENV['MAIL_USERNAME'];
        $this->mailer->Password = $_ENV['MAIL_PASSWORD'];

        $this->mailer->setFrom($_ENV['MAIL_SENDER_ADDRESS'], $_ENV['MAIL_SENDER_NAME']);
        $this->mailer->addReplyTo($_ENV['MAIL_SENDER_ADDRESS'], $_ENV['MAIL_SENDER_NAME']);
    }

    function send()
    {
        $this->mailer->send();
    }

    public function addAddress(string $address, string $name = '')
    {
        $this->mailer->addAddress($address, $name);
    }

    public function setSubject(string $subject)
    {
        $this->mailer->Subject = $subject;
    }

    public function setBody(string $body)
    {
        $this->mailer->Body = $body;
        $this->mailer->AltBody = $body;
    }

    public function setAltBody(string $altBody)
    {
        $this->mailer->AltBody = $altBody;
    }

    public function isHTML(?bool $bool = null)
    {
        $this->mailer->isHTML($bool);
    }
}
