<?php

namespace Mailer\Server\Smtp;

use Mailer\Model\SentMail;
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
     *   Mail structure whose minimal data must be set:
     *     - from
     *     - to
     *   That's pretty much everything, you are allowed to
     */
    public function sendMail(SentMail $mail);
}
