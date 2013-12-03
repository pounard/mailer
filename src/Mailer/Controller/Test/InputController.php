<?php

namespace Mailer\Controller\Test;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;

class InputController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array(), 'test/input');
    }
}
