<?php

namespace Mailer\Core;

use Mailer\Dispatch\RequestInterface;
use Mailer\Error\ConfigError;
use Mailer\Server\Imap\Impl\CachedMailReader;
use Mailer\Server\Imap\Impl\RcubeImapMailReader;
use Mailer\Server\Native\PhpImapMailReader;
use Mailer\Server\Native\PhpSmtpServer;
use Mailer\Server\Proxy\MailReader;

use Config\Impl\Memory\MemoryBackend;
use Doctrine\Common\Cache\RedisCache;
use Mailer\Server\Imap\Index;

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
        $config)
    {
        self::prepareEnvironment();

        $container = new Container();
        $component->setContainer($container);

        $pimple = $container->getInternalContainer();

        // Set some various services
        foreach ($config['services'] as $key => $value) {
            if (is_callable($value)) {
                $pimple[$key] = function () use ($container, $value) {
                    call_user_func($value, $container);
                };
            } else if (class_exists($value)) {
                $pimple[$key] = function () use ($container, $value) {
                    $service = new $value();
                    if ($service instanceof ContainerAwareInterface) {
                        $service->setContainer($container);
                    }
                    return $service;
                };
            } else {
                throw new ConfigError(sprintf("Invalid service definition '%s'", $key));
            }
        }

        $pimple['session']->start();

        // Server wide configuration
        /*
        $pimple['config'] = function () use ($config) {
            return new MemoryBackend($config['config']);
        };
         */
        $pimple['config'] = $config['config'];
        if (!isset($config['config']['charset'])) {
          $config['config']['charset'] = "UTF-8";
        }
        mb_internal_encoding($config['config']['charset']);

        // @todo
        $pimple['userconfig'] = array();

        // Services
        $pimple['index'] = function () use ($container, $config) {

            // @todo Find a better way to handle cache
            $cache = null;
            if (isset($config['redis'])) {
                $redis = new \Redis();
                $redis->connect($config['redis']['host']);
                $cache = new RedisCache();
                $cache->setRedis($redis);
            }

            $reader = new RcubeImapMailReader();
            $reader->setOptions($config['servers']['imap']);

            $index = new Index($reader, $cache);
            $index->setContainer($container);

            return $index;
        };
        $pimple['smtp'] = function () use ($container, $config) {
            $service = new PhpSmtpServer();
            $service->setOptions($config['servers']['stmp']);
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($container);
            }
            return $service;
        };
    }
}
