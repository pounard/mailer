<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Server\MailReaderInterface;

/**
 * Return parameters from the request
 */
class FolderController extends AbstractMailController
{
    public function getAction(RequestInterface $request, array $args)
    {
        $server = $this->getServer();

        switch (count($args)) {

            case 0:
                return $server->getFolderMap();

            case 1:
                return $server->getFolder($args[0]);

            case 2:
                switch ($args[1]) {

                    case 'list':
                        return $server->getThreads($args[0]);

                    case 'refresh':
                        // Force refresh to have at least the last update time
                        // and the new unread count
                        $folder = $server->getFolder($args[0], true);

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
                            $list = $server->getThreadsSince($args[0], $since);
                        } else {
                            $list = array();
                        }

                        return array('folder' => $folder, 'list' => $list);

                    default:
                        throw new LogicError(sprintf("Invalid option '%s'", $args[1]));
              }

            default:
                throw new LogicError("Too many arguments");
        }
    }
}
