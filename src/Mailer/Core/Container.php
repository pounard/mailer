<?php

namespace Mailer\Core;

/**
 * Main service container
 */
class Container
{
    /**
     * @var \Pimple
     */
    private $container;

    /**
     * 
     * @var unknown
     */
    private $parameters;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->container = new \Pimple();
    }

    /**
     * Get internal container
     *
     * @return \Pimple
     */
    public function getInternalContainer()
    {
        return $this->container;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->container['config'];
    }

    /**
     * Get logged in user configuration
     *
     * @return array
     */
    public function getUserConfig()
    {
        return $this->container['userconfig'];
    }

    /**
     * Get default charset
     */
    public function getDefaultCharset()
    {
        $config = $this->getConfig();

        return $config['charset'];
    }

    /**
     * Get session
     *
     * @return \Mailer\Core\Session
     */
    public function getSession()
    {
        return $this->container['session'];
    }

    /**
     * Get model object factory
     *
     * @return \Mailer\Model\Factory\DefaultFactory
     */
    public function getModelFactory()
    {
        return $this->container['modelfactory'];
    }

    /**
     * Get filter factory
     *
     * @return \Mailer\View\Helper\FilterFactory
     */
    public function getFilterFactory()
    {
        return $this->container['filterfactory'];
    }

    /**
     * Get mail reader
     *
     * @return \Mailer\Server\Imap\Index
     */
    public function getIndex()
    {
        return $this->container['index'];
    }
}
