<?php

namespace Mailer\Model\Server;

class ImapServer extends AbstractServer
{
    const PORT = 143;

    const PORT_SECURE = 943;

    public function getDefaultPort($isSecure)
    {
        return $isSecure ? self::PORT_SECURE : self::PORT;
    }
}
