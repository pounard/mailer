<?php

namespace Mailer\Controller\Api;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\NotFoundError;
use Mailer\Error\NotImplementedError;

/**
 * Return parameters from the request
 */
class MailController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        switch (count($args)) {

            case 1:
                // @todo List mails in the folder
                throw new NotImplementedError();

            case 2:
                return $this
                    ->getContainer()
                    ->getIndex()
                    ->getMailboxIndex($args[0])
                    ->getMail((int)$args[1]);

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

    public function patchAction(RequestInterface $request, array $args)
    {
        switch (count($args)) {

            case 2:
                $mailbox = $this
                    ->getContainer()
                    ->getIndex()
                    ->getMailboxIndex($args[0]);

                $updates = array();
                foreach ($request->getContent() as $flag => $toggle) {
                    $mailbox->flag($args[1], $flag, (bool)$toggle);
                }
                break;

            default:
                throw new NotFoundError("Identifier must be 'MAILDIR' or 'MAILDIR/UID'");
        }
    }
}
