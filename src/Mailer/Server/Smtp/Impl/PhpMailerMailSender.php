<?php

namespace Mailer\Server\Smtp\Impl;

use Mailer\Model\Mail;
use Mailer\Server\AbstractServer;
use Mailer\Server\Smtp\MailSenderInterface;

/**
 * Uses the PHPMailer as sending backend.
 */
class PhpMailerMailSender extends AbstractServer implements
    MailSenderInterface
{
    public function getDefaultPort($isSecure)
    {
        return $isSecure ? MailSenderInterface::PORT_SECURE : MailSenderInterface::PORT;
    }

    public function isConnected()
    {
        return false; // Sorry...
    }

    public function connect()
    {
        return true; // Sorry...
    }

    /**
     * Get client
     *
     * @return \PHPMailer
     */
    private function getClient()
    {
        $client = new \PHPMailer(true);

        $client->isSMTP();
        $client->Host = $this->getHost();
        $client->Port = $this->getPort();
        $client->SMTPAuth = true;
        $client->Username = $this->getUsername();
        $client->Password = $this->getPassword();

        if ($this->isSecure()) {
            $client->SMTPSecure = 'ssl'; // @todo Handle TLS
        }

        return $client;
    }

    public function sendMail(Mail $mail, array $headers)
    {
        $client = $this->getClient();

        /*
         * Example from https://github.com/PHPMailer/PHPMailer
         *
        $mail->From = 'from@example.com';
        $mail->FromName = 'Mailer';
        $mail->addAddress('josh@example.net', 'Josh Adams');  // Add a recipient
        $mail->addAddress('ellen@example.com');               // Name is optional
        $mail->addReplyTo('info@example.com', 'Information');
        $mail->addCC('cc@example.com');
        $mail->addBCC('bcc@example.com');

        $mail->WordWrap = 50;                                 // Set word wrap to 50 characters
        $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = 'Here is the subject';
        $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
         */

        $client->From = $mail->getFrom()->getMail();
        $client->FromName = $mail->getFrom()->getDisplayName();
        foreach ($mail->getTo() as $person) {
            $client->AddAddress($person->getMail(), $person->getDisplayName());
        }
        foreach ($mail->getCc() as $person) {
            $client->addCC($person->getMail(), $person->getDisplayName());
        }
        foreach ($mail->getBcc() as $person) {
            $client->addBCC($person->getMail(), $person->getDisplayName());
        }
        foreach ($mail->getAttachements() as $filename) {
            $client->addAttachment($filename);
        }
        $client->isHTML(false);
        $client->Subject = $mail->getSubject();
        $client->Body = $mail->getBodyPlain();

        $client->send();
    }
}
