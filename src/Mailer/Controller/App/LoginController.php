<?php

namespace Mailer\Controller\App;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\RequestInterface;
use Mailer\Security\Account;
use Mailer\View\View;
use Mailer\Error\LogicError;

class LoginController extends AbstractController
{
    public function isAuthorized(RequestInterface $request, array $args)
    {
        return $this
            ->getContainer()
            ->getSession()
            ->isAuthenticated();
    }

    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array(), 'app/login');
    }

    public function postAction(RequestInterface $request, array $args)
    {
        /*
        // @todo Test login and password
        $posted = $request->getContent();
        $account = new Account(-1, $posted['username'], $posted['username']);
        $_SESSION['account'] = $account;
        //return new RedirectResponse(null);
         */

        // FIXME Very very bad (using globals).
        $container = $this->getContainer();
        if ($container['auth']->authenticate($_POST['username'], $_POST['password'])) {
            // Yeah! Success.
            if (!$container->getSession()->regenerate(new Account(-1, $_POST['username'], $_POST['password']))) {
                throw new LogicError("Could not create session");
            }
            return new RedirectResponse('');
        } else {
            // Bouh! Wrong credentials.
            // Redirect to the very same page but using GET
            return new RedirectResponse($request->getResource());
        }
    }
}
