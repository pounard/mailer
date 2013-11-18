<?php

namespace Mailer\Security\Auth;

/**
 * Simple authentication manager
 */
interface AuthProviderInterface
{
    /**
     * Authenticate account
     *
     * @param string $username
     * @param string $password
     *
     * @return boolean
     */
    public function authenticate($username, $password);
}
