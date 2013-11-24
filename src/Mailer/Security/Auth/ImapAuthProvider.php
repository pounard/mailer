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
        $mailReader = $this->getContainer()->getMailReader();
        $mailReader->setCredentials($username, $password);

        return $mailReader->connect();
    }
}
