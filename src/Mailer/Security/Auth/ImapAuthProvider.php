<?php

namespace Mailer\Security\Auth;

use Mailer\Core\AbstractContainerAware;

class ImapAuthProvider extends AbstractContainerAware implements AuthProviderInterface
{
    public function authenticate($username, $password)
    {
        $container = $this->getContainer();
        $server = $container['imap'];
    }
}
