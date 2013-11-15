<?php

namespace Mailer\Core;

abstract class AbstractContainerAware implements ContainerAwareInterface
{
    /**
     * @var \Pimple
     */
    private $container;

    /**
     * Default constructor
     *
     * Ensures that we always have a valid container
     */
    public function __construct()
    {
        $this->container = new \Pimple();
    }

    public function setContainer(\Pimple $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
