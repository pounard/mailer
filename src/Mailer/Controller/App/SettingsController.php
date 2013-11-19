<?php

namespace Mailer\Controller\App;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;

class SettingsController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array(), 'app/settings/index');
    }
}