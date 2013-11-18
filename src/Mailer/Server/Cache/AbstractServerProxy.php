<?php

namespace Mailer\Server\Cache;

use Mailer\Core\AbstractContainerAware;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Server\ServerInterface;

/**
 * Imap server connection using the PHP IMAP extension
 */
abstract class AbstractServerProxy extends AbstractContainerAware implements
    ServerInterface
{
    /**
     * @var ServerInterface
     */
    protected $nested;

    /**
     * Default constructor
     *
     * @param ServerInterface $nested
     */
    public function __construct(ServerInterface $nested)
    {
        $this->nested = $nested;
    }

    public function getHost()
    {
        return $this->nested->getHost();
    }

    public function getPort()
    {
        return $this->nested->getPort();
    }

    public function getUsername()
    {
        return $this->nested->getUsername();
    }

    public function getPassword()
    {
        return $this->nested->getPassword();
    }

    public function setCredentials($username, $password, $reconnect = false)
    {
        $this->nested->setCredentials($username, $password, $reconnect);
    }

    public function isSecure()
    {
        return $this->nested->isSecure();
    }

    public function acceptsInvalidCertificate()
    {
        return $this->nested->acceptsInvalidCertificate();
    }

    public function getDefaultPort($isSecure)
    {
        return $this->nested->getDefaultPort($isSecure);
    }

    public function connect()
    {
        return $this->nested->connect();
    }

    public function isConnected()
    {
        return $this->nested->isConnected();
    }

    public function setOptions(array $options)
    {
        $this->nested->setOptions($options);
    }

    public function getOptions()
    {
        return $this->nested->getOptions();
    }

    public function setContainer(\Pimple $container)
    {
        parent::setContainer($container);

        if ($this->nested instanceof ContainerAwareInterface) {
            $this->nested->setContainer($container);
        }
    }
}
