<?php

namespace Mailer\Core;

use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\RequestInterface;
use Mailer\Model\Server\Native\PhpImapMailReader;
use Mailer\Model\Server\Native\PhpSmtpServer;

use Config\Impl\Memory\MemoryBackend;

/**
 * OK this is far from ideal nevertheless it works
 */
class Bootstrap
{
    /**
     * Tell if the current environment has been prepared
     */
    static private $environmentPrepared = false;

    /**
     * Prepare the environement
     */
    static public function prepareEnvironment()
    {
        if (self::$environmentPrepared) {
            return;
        }

        self::$environmentPrepared = true;

        mb_internal_encoding("UTF-8");
    }

    /**
     * Bootstrap core application
     */
    static public function bootstrap(
        ContainerAwareInterface $component,
        RequestInterface $request,
        $config)
    {
        self::prepareEnvironment();

        $container = $component->getContainer();

        // Set some global dynamic parameters
        // FIXME
        $container['basepath'] = '';

        // Server wide configuration
        $container['config'] = function () use ($config) {
            return new MemoryBackend($config['config']);
        };

        // Services
        $container['imap'] = function () use ($config) {
            return new PhpImapMailReader($config['servers']['imap']);
        };
        $container['smtp'] = function () use ($config) {
            return new PhpSmtpServer($config['servers']['stmp']);
        };
    }
}
