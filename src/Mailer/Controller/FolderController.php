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
                return $index->getFolderMap(
                    $request->getOption('parent', null),
                    /*!*/(bool)$request->getOption('all'),
                    (bool)$request->getOption('refresh'));

            case 1:
                return $index->getFolder($args[0], (bool)$request->getOption('refresh'));

            case 2:
                switch ($args[1]) {

                    case 'list':
                        return $index->getThreads($args[0], (bool)$request->getOption('refresh'));

                    case 'refresh':
                        // Force refresh to have at least the last update time
                        // and the new unread count
                        $folder = $index->getFolder($args[0], true);

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

                        return array('folder' => $folder, 'list' => $list);

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
