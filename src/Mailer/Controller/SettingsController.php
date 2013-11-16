<?php

namespace Mailer\Controller;

use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;

class SettingsController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array(), 'settings/index');
    }
}
