<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Server\MailReaderInterface;

class AbstractMailController extends AbstractController
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
}
