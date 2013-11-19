<?php

namespace Mailer\Core;

use Mailer\Dispatch\RequestInterface;
use Mailer\Error\ConfigError;
use Mailer\Server\Cache\CachedMailReader;
use Mailer\Server\Native\PhpImapMailReader;
use Mailer\Server\Native\PhpSmtpServer;
use Mailer\Server\Rcube\RcubeImapMailReader;

use Config\Impl\Memory\MemoryBackend;
use Doctrine\Common\Cache\RedisCache;

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
        $container['basepath'] = '/';
        $container['request'] = $request;

        // Set some various services
        foreach ($config['services'] as $key => $value) {
            if (is_callable($value)) {
                $container[$key] = function () use ($container, $value) {
                    call_user_func($value, $container);
                };
            } else if (class_exists($value)) {
                $container[$key] = function () use ($container, $value) {
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

        $container['session']->start();

        // Server wide configuration
        $container['config'] = function () use ($config) {
            return new MemoryBackend($config['config']);
        };

        // Services
        $container['imap'] = function () use ($container, $config) {
            if (isset($config['redis'])) {
                $redis = new \Redis();
                $redis->connect($config['redis']['host']);
                $cache = new RedisCache();
                $cache->setRedis($redis);
            }
            if (isset($cache)) {
                $service = new CachedMailReader(new RcubeImapMailReader(), $cache);
            } else {
                $service = new RcubeImapMailReader();
            }

            $service->setOptions($config['servers']['imap']);
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($container);
            }
            return $service;
        };
        $container['smtp'] = function () use ($container, $config) {
            $service = new PhpSmtpServer();
            $service->setOptions($config['servers']['stmp']);
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($container);
            }
            return $service;
        };
    }
}
