<?php

namespace Mailer\Controller\Api;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\NotFoundError;

/**
 * Return parameters from the request
 */
class ThreadController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        switch (count($args)) {

            case 1:
                return $this
                    ->getContainer()
                    ->getIndex()
                    ->getMailboxIndex($args[0])
                    ->getThreads(
                        $this->getQueryFromRequest($request),
                        (bool)$request->getOption('refresh')
                    );

            case 2:
                return $this
                    ->getContainer()
                    ->getIndex()
                    ->getMailboxIndex($args[0])
                    ->getThread(
                        (int)$args[1],
                        (bool)$request->getOption('refresh')
                    );

            case 3:
                switch ($args[2]) {

                    case 'mail':
                        return $this
                            ->getContainer()
                            ->getIndex()
                            ->getMailboxIndex($args[0])
                            ->getThreadMails(
                                (int)$args[1],
                                $this->getQueryFromRequest($request),
                                (bool)$request->getOption('refresh')
                            );

                    default:
                        throw new NotFoundError(sprintf("Invalid sub-collection '%s'", $args[2]));
                }

            default:
                throw new NotFoundError("Identifier must be 'MAILDIR' or 'MAILDIR/UID'");
        }
    }
}
