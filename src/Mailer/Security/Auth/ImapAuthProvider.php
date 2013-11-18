<?php

namespace Mailer\Security\Auth;

use Mailer\Core\AbstractContainerAware;

/**
 * Authenticate the user using the IMAP server instance
 */
class ImapAuthProvider extends AbstractContainerAware implements AuthProviderInterface
{
    public function authenticate($username, $password)
    {
        $container = $this->getContainer();
        $server = $container['imap'];
        $server->setCredentials($username, $password);

        return $server->connect();
    }
}
