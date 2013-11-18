<?php

namespace Mailer\Controller\App;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;
use Mailer\Dispatch\Http\RedirectResponse;

class LogoutController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        $container = $this->getContainer();
        $container['session']->destroy();

        return new RedirectResponse('app/login');
    }
}
