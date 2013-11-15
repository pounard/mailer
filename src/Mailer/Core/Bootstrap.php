<?php

namespace Mailer\Core;

use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\RequestInterface;
use Mailer\Model\Server\ImapServer;
use Mailer\Model\Server\SmtpServer;

/**
 * OK this is far from ideal nevertheless it works
 */
class Bootstrap
{
    static public function bootstrap(
        ContainerAwareInterface $component,
        RequestInterface $request,
        $config)
    {
        $container = $component->getContainer();

        // @todo
        // Add items to the container

        $container['imap'] = function () use ($config) {
            return new ImapServer($config['imap']);
        };
        $container['smtp'] = function () use ($config) {
            return new SmtpServer($config['stmp']);
        };
    }
}
