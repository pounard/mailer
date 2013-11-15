<?php

namespace Mailer\Dispatch\Router;

/**
 * Router interface
 */
interface RouterInterface
{
    /**
     * Find controller for the given resource path
     *
     * @param string $resource
     *
     * @return (controller, args)
     */
    public function findController($resource);
}
