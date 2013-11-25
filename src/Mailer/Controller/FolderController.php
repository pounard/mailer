<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Server\Imap\MailReaderInterface;
use Mailer\Server\Imap\Query;

/**
 * Return parameters from the request
 */
class FolderController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        $index = $this->getContainer()->getIndex();

        switch (count($args)) {

            case 0:
                $onlySubs = /*!*/(bool)$request->getOption('all');
                $refresh  = (bool)$request->getOption('refresh');
                $parent   = $request->getOption('parent', null);

                if ($parent) {
                    return $index->getMailboxIndex($parent)->getChildren($onlySubs, $refresh);
                } else {
                    return $index->getMailboxMap($onlySubs, $refresh);
                }

            case 1:
                return $index->getMailbox($args[0], (bool)$request->getOption('refresh'));

            case 2:
                switch ($args[1]) {

                    case 'list':
                        return $index
                            ->getMailboxIndex($args[0])
                            ->getThreads(
                                $this->getQueryFromRequest($request),
                                (bool)$request->getOption('refresh')
                            );

                    case 'refresh':
                        $mailboxIndex = $index->getMailboxIndex($args[0]);

                        /*
                        if ($since = $request->getOption('since', null)) {
                            if (is_numeric($since)) {
                                $since = new \DateTime('@' . $since);
                            } else {
                                // Per convention we only access ISO86
                                if (!$since = \DateTime::createFromFormat(\DateTime::ISO8601, $since)) {
                                    throw new \LogicError("Invalid date format, not a UNIX timestamps or an ISO8601 valid string");
                                }
                            }
                        }

                        if ($since < $folder->getLastUpdate()) {
                            $list = $index->getThreadsSince($args[0], $since);
                        } else {
                            $list = array();
                        }
                         */

                        return array(
                            'folder' => $mailboxIndex->getInstance(true),
                            'list'   => $mailboxIndex->getThreads(
                                $this->getQueryFromRequest($request),
                                true
                            ),
                        );

                    default:
                        throw new LogicError(sprintf("Invalid argument '%s'", $args[1]));
              }

            case 3:
                switch ($args[1]) {

                    case 'thread':
                        return $index->getThread(
                            $args[0],
                            (int)$args[2],
                            ($request->getOption('reverse', 0) ? Query::ORDER_DESC : Query::ORDER_ASC),
                            (bool)$request->getOption('complete', true),
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
