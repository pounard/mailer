<?php

namespace Mailer\Core;

interface ContainerAwareInterface
{
    /**
     * Set container
     *
     * @param \Pimple $container
     */
    public function setContainer(\Pimple $container);

    /**
     * Get container
     *
     * @return \Pimple
     */
    public function getContainer();
}
