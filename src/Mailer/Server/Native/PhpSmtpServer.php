<?php

namespace Mailer\Server\Native;

use Mailer\Server\AbstractServer;

class PhpSmtpServer extends AbstractServer
{
    const PORT = 25;

    const PORT_SECURE = 495;

    public function getDefaultPort($isSecure)
    {
        return $isSecure ? self::PORT_SECURE : self::PORT;
    }
}
