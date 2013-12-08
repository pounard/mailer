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

    public function deleteAction(RequestInterface $request, array $args)
    {
        switch (count($args)) {

            case 2:
                $mailbox = $this
                    ->getContainer()
                    ->getIndex()
                    ->getMailboxIndex($args[0])
                    ->deleteMail($args[1]);
                break;

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
                    switch ($flag) {

                        case 'seen':
                        case 'isSeen':
                            $flag = 'seen';
                            break;

                        case 'starred':
                        case 'isFlagged':
                        case 'flagged':
                            $flag = 'flagged';
                            break;

                        default: // FIXME Find the right error code to apply
                            throw new NotImplementedError("Cannot proceed with flag '%s'", $flag);
                    }
                    $updates[$flag] = $this->parseBoolean($toggle);
                }

                if (!empty($updates)) {
                    foreach ($updates as $flag => $toggle) {
                        $mailbox->flag($args[1], $flag, $toggle);
                    }
                }
                break;

            default:
                throw new NotFoundError("Identifier must be 'MAILDIR' or 'MAILDIR/UID'");
        }
    }
}
