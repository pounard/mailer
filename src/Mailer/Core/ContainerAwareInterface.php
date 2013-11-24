<?php

namespace Mailer\Core;

interface ContainerAwareInterface
{
    /**
     * Set container
     *
     * @param Container $container
     */
    public function setContainer(Container $container);

    /**
     * Get container
     *
     * @return Container
     */
    public function getContainer();
}
