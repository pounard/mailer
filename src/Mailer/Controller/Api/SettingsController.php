<?php

namespace Mailer\Controller\Api;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;

class SettingsController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        $container = $this->getContainer();

        return new View(array(
            'global' => $container->getConfig(),
            'user'   => $container->getUserConfig(),
        ));
    }
}
