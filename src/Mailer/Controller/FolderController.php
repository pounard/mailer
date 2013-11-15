<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Model\Server\ImapServer;

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
     *    $server = (ImapServer)$this->getContainer()['imap'];
     *
     * But sadly PHP is really a very wrong language.
     *
     * @return ImapServer
     */
    public function getServer()
    {
        $container = $this->getContainer();
        $server = $container['imap'];

        if (!$server instanceof ImapServer) {
            throw new \LogicError("Oups");
        }

        return $server;
    }

    public function getAction(RequestInterface $request, array $args)
    {
        $server = $this->getServer();

        if (!empty($args)) {
            $name = array_shift($args);
            $folder = $server->getFolder($name);
        } else {
            $folderList = $server->getFolderMap();
        }
    }
}
