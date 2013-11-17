<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Model\Server\MailReaderInterface;

/**
 * Return parameters from the request
 */
class FolderController extends AbstractController
{
    /**
     * Get server
     *
     * Don't dream about the right software design this just gives my IDE
     * the opportunity to a better autocompletion; If PHP wouldn't be a so
     * wrong language I would have written:
     *
     *    $server = (ImapMailReader)$this->getContainer()['imap'];
     *
     * But sadly PHP is really a very wrong language.
     *
     * @return ImapMailReader
     */
    public function getServer()
    {
        $container = $this->getContainer();
        $server = $container['imap'];

        if (!$server instanceof MailReaderInterface) {
            throw new LogicError("Oups");
        }

        return $server;
    }

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
                        return $server->getThreadSummary($args[0]);

                    default:
                        throw new LogicError(sprintf("Invalid option '%s'", $args[1]));
                }

            default:
                throw new LogicError("Too many arguments");
        }
    }
}
