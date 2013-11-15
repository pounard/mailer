<?php

namespace Mailer\Controller;

use Mailer\Dispatch\RequestInterface;

/**
 * Controller interface
 */
interface ControllerInterface
{
    public function dispatch(RequestInterface $request);
}
