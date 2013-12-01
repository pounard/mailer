<?php

namespace Mailer\Controller\App;

use Mailer\Controller\AbstractController;
use Mailer\Core\Message;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Security\Account;
use Mailer\View\View;

class LoginController extends AbstractController
{
    public function isAuthorized(RequestInterface $request, array $args)
    {
        return !$this
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
        $content = $request->getContent();
        $container = $this->getContainer();
        $pimple = $container->getInternalContainer();

        if ($pimple['auth']->authenticate($content['username'], $content['password'])) {
            // Yeah! Success.
            if (!$container->getSession()->regenerate(new Account(-1, $content['username'], $content['password']))) {
                $container->getMessager()->addMessage("Could not create your session", Message::TYPE_ERROR);
                throw new LogicError("Could not create session");
            }
            $container->getMessager()->addMessage("Welcome back!", Message::TYPE_SUCCESS);
            return new RedirectResponse('');
        } else {
            // Bouh! Wrong credentials.
            $container->getMessager()->addMessage("Unable to login, please check your account name and password", Message::TYPE_ERROR);

            // Redirect to the very same page but using GET
            return new RedirectResponse($request->getResource());
        }
    }
}
