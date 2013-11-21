<?php

namespace Mailer\Controller;

use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;

class ThreadController extends AbstractMailController
{
    public function getAction(RequestInterface $request, array $args)
    {
        $server = $this->getServer();

        switch (count($args)) {

            case 0:
                throw new LogicError("Too few arguments");

            case 1:
                return $server->getThread(
                    $args[0],
                    (bool)$request->getOption('complete', true),
                    (bool)$request->getOption('refresh')
                );

            case 2:
                switch ($args[1]) {

                    case 'list':
                        return $server->getThreadMails(
                            $id,
                            (bool)$request->getOption('refresh')
                        );

                    default:
                        throw new LogicError(sprintf("Invalid argument '%s'", $args[1]));
              }

            default:
                throw new LogicError("Too many arguments");
        }
    }
}
