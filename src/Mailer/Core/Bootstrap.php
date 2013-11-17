<?php

namespace Mailer\Core;

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
        date_default_timezone_set('CET');
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
        // FIXME -- I said dynamic...
        $container['basepath'] = '';

        // Server wide configuration
        $container['config'] = function () use ($config) {
            return new MemoryBackend($config['config']);
        };

        // Services
        $container['imap'] = function () use ($container, $config) {
            $service = new PhpImapMailReader($config['servers']['imap']);
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($container);
            }
            return $service;
        };
        $container['smtp'] = function () use ($container, $config) {
            $service = new PhpSmtpServer($config['servers']['stmp']);
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($container);
            }
            return $service;
        };
    }
}