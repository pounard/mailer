<?php

namespace Mailer\Controller\App;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\RequestInterface;
use Mailer\Security\Account;
use Mailer\View\View;

class LoginController extends AbstractController
{
    public function isAuthorized(RequestInterface $request, array $args)
    {
        $container = $this->getContainer();

        return !$container['session']->isAuthenticated();
    }

    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array(), 'app/login');
    }

    public function postAction(RequestInterface $request, array $args)
    {
        // @todo Test login and password
        $posted = $request->getContent();
        $account = new Account(-1, $posted['username'], $posted['username']);
        $_SESSION['account'] = $account;
        //return new RedirectResponse(null);
    }
}
