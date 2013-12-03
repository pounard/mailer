<?php

namespace Mailer\Server\Smtp;

use Mailer\Model\Mail;
use Mailer\Server\ServerInterface;

/**
 * SMTP connection
 */
interface MailSenderInterface extends ServerInterface
{
    /**
     * Default SMTP port
     */
    const PORT = 25;

    /**
     * Default SMTPS port
     */
    const PORT_SECURE = 465;

    /**
     * Send all the things
     *
     * @param Mail $mail
     *   Mail to send; For body content use the Multipart object you can get
     *   calling the Mail::getStructure() method
     *,@param string[] $headers
     *   Because we want the backend to be the simplest possible in order to
     *   be easy to swap out, and because we want the headers to be built in
     *   a reproductible manner, the upper layer will give you this one you
     *   lucky guy!
     */
    public function sendMail(Mail $mail, array $headers);
}
