<?php

namespace Mailer\Server\Smtp\Impl;

use Mailer\Mime\AbstractPart;
use Mailer\Mime\Multipart;
use Mailer\Mime\Part;
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

    private function parseStructure(\PHPMailer $client, AbstractPart $part)
    {
        $charset = null;

        if ($part instanceof Multipart) {
            foreach ($part as $child) {
                $this->parseStructure($client, $child);
            }
        } else if ($part instanceof Part) {

            $charset = $part->getParameter('charset');

            switch ($part->getType()) {

                case 'text':
                    switch ($part->getSubtype()) {

                        case 'plain':
                            if ($client->isHTML()) {
                                if (empty($client->AltBody)) {
                                     $client->AltBody = $part->getContentsReal();
                                } // Else sorry but this sender allows only one body
                            } else {
                                if (empty($client->Body)) {
                                    $client->Body = $part->getContentsReal();
                                } // Else sorry but this sender allows only one body
                            }
                            break;

                        case 'html';
                            if ('text/html' === $client->ContentType) {
                                if (empty($client->Body)) {
                                    $client->Body = $part->getContentsReal();
                                } // Else sorry but this sender allows only one body
                            } else {
                                // Moves out current body and put into AltBody
                                if (!empty($client->Body)) {
                                    $client->AltBody = $client->Body;
                                }
                                $client->Body = $part->getContentsReal();
                                $client->isHTML(true);
                            }
                            break;

                        default:
                            // @todo What to do with this?
                    }
                    break;

                default:
                    // Consider all other parts as file attachements
                    // @todo
                    break;
            }
        }

        if (empty($client->CharSet) && null !== $charset) {
            $client->CharSet = $charset;
        }
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
        $client->Subject = $mail->getSubject();

        if ($structure = $mail->getStructure()) {
            $this->parseStructure($client, $structure);
        }

        $client->send();
    }
}
