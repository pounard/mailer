<?php

namespace Mailer\Server;

use Mailer\Core\AbstractContainerAware;
use Mailer\Error\LogicError;

abstract class AbstractServer extends AbstractContainerAware implements
    ServerInterface
{
    private $host = 'localhost';

    private $port;

    private $username;

    private $password;

    private $secure = true;

    private $acceptInvalidCert = true;

    private $options = array();

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setCredentials($username, $password, $reconnect = false)
    {
        if (!$reconnect && $this->isConnected()) {
            throw new LogicError(sprintf("Cannot change credential while connected"));
        }

        $options = array(
            'username' => $username,
            'password' => $password,
        );

        $this->initFromOptions($options);
    }

    public function isSecure()
    {
        return $this->secure;
    }

    public function acceptsInvalidCertificate()
    {
        return $this->acceptInvalidCert;
    }

    protected function initFromOptions(array $options)
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
        if (isset($options['secure_invalid'])) {
            $this->acceptInvalidCert = (bool)$options['secure_invalid'];
        }

        if (!isset($options['port'])) {
            $this->port = $this->getDefaultPort($this->secure);
        }
    }

    public function setOptions(array $options)
    {
        if ($this->isConnected()) {
            throw new LogicError("Cannot change options while connected");
        }

        $this->initFromOptions($options);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setContainer(\Pimple $container)
    {
        parent::setContainer($container);

        if ($account = $container['session']->getAccount()) {
            $this->setCredentials(
                $account->getUsername(),
                $account->getPassword()
            );
        }
    }
}
