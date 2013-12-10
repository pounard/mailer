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
                            if ('text/html' === $client->ContentType) {
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

    public function sendMail(Mail $mail, array $headers, $resource = null)
    {
        $client = $this->getClient();

        foreach ($headers as $name => $value) {

            // This is absolutely stupid by PHPMailer will raise
            // error if some headers have not been explictely set:
            // seriously fuck you. Some others will be duplicated
            // or empty if not set explicitly (even worse).
            switch ($name) {

                case 'From':
                    $client->From = $value;
                    break;

                case 'To':
                    // Just ignore it, we will set it manually
                    break;

                case 'Message-ID':
                    $client->MessageID = $value;
                    break;

                case 'Subject':
                    $client->Subject = $value;
                    break;

                default:
                    // We can't use addCustomHeader() method because
                    // it will break our headers if they are empty
                    // values
                    if (empty($value)) {
                        $client->addCustomHeader($name . ":");
                    } else {
                        $client->addCustomHeader($name, $value);
                    }
                    break;
            }
        }

        foreach ($mail->getTo() as $person) {
            $client->addAddress(
                $person->getMail(),
                $person->getDisplayName()
            );
        }

        if ($structure = $mail->getStructure()) {
            $this->parseStructure($client, $structure);
        }

        if (null === $client->CharSet) {
            $client->CharSet = $this->getContainer()->getDefaultCharset();
        }

        $client->send();
    }
}
