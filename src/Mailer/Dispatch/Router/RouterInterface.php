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
     * @return ControllerInterface
     */
    public function findController($resource);
}
