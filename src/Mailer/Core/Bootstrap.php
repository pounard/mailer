<?php

namespace Mailer\Core;

use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\RequestInterface;

/**
 * OK this is far from ideal nevertheless it works
 */
class Bootstrap
{
    static public function bootstrap(
        ContainerAwareInterface $component,
        RequestInterface $request,
        $config)
    {
        $container = $component->getContainer();

        // @todo
        // Add items to the container
    }
}
