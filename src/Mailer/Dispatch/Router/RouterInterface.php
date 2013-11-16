<?php

namespace Mailer\Dispatch\Router;

use Mailer\Dispatch\RequestInterface;

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
    public function findController(RequestInterface $request);
}
