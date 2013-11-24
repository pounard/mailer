<?php

namespace Mailer\Core;

abstract class AbstractContainerAware implements ContainerAwareInterface
{
    /**
     * @var Container
     */
    private $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
