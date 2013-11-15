<?php

namespace Mailer\Model\Server;

class SmtpServer extends AbstractServer
{
    const PORT = 25;

    const PORT_SECURE = 495;

    public function getDefaultPort($isSecure)
    {
        return $isSecure ? self::PORT_SECURE : self::PORT;
    }
}
