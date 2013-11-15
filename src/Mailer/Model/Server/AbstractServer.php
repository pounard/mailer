<?php

namespace Mailer\Model\Server;

use Mailer\Error\LogicError;

abstract class AbstractServer
{
    private $host = 'localhost';

    private $port;

    private $username;

    private $password;

    private $secure = true;

    /**
     * Default constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (isset($options['host'])) {
            $this->host = (string)$options['host'];
        }
        if (isset($options['port'])) {
            $this->port = (int)$options['port'];
        }
        if (isset($options['username'])) {
            $this->username = (string)$options['username'];
        }
        if (isset($options['password'])) {
            $this->password = (string)$options['password'];
        }
        if (isset($options['secure'])) {
            $this->secure = (bool)$options['secure'];
        }

        if (!isset($options['port'])) {
            $this->port = $this->getDefaultPort($this->secure);
        }

        if (empty($this->username)) {
            throw new LogicError("Cannot use a server without a username");
        }
    }

    abstract public function getDefaultPort($isSecure);
}
