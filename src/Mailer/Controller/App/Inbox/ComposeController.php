<?php

namespace Mailer\Controller\App\Inbox;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;
use Mailer\Error\NotImplementedError;

class ComposeController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array(), 'app/inbox/compose');
    }

    public function postAction(RequestInterface $request, array $args)
    {
        throw new NotImplementedError();
    }
}
