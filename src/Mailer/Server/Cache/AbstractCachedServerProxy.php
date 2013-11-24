<?php

namespace Mailer\Server\Cache;

use Mailer\Server\ServerInterface;

use Doctrine\Common\Cache\Cache;
use Mailer\Core\Container;

/**
 * Imap server connection using the PHP IMAP extension
 */
abstract class AbstractCachedServerProxy extends AbstractServerProxy
{
    /**
     * Default lifetime in seconds
     */
    const DEFAULT_LIFETIME = 43200; // 12 hours

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var suffix of the cache prefix
     */
    private $type;

    /**
     * @var int
     */
    private $defaultLifetime = self::DEFAULT_LIFETIME;

    /**
     * Default constructor
     *
     * @param ServerInterface $nested
     * @param string $prefix
     */
    public function __construct(ServerInterface $nested, Cache $cache, $prefix = null, $defaultLifetime = null)
    {
        parent::__construct($nested);

        $this->cache = $cache;
        $this->prefix = $prefix;
        // Why MD5? Why not? It will shorten it in most cases
        $this->type = md5(get_class($nested));
        if (null !== $defaultLifetime) {
            $this->defaultLifetime = (int)$defaultLifetime;
        }
    }

    /**
     * Get cache key for this cache entry
     *
     * @param string $name
     *
     * @return string
     */
    protected function getKey($id)
    {
        if ($this->prefix) {
            return $this->type . ':' . $this->prefix . ':' . $id;
        } else {
            return $this->type . ':g:' . $id;
        }
    }

    public function setContainer(Container $container)
    {
        parent::setContainer($container);

        // Per default cache prefix will always be user dependent
        if (null === $this->prefix) {
            $this->prefix = $container->getSession()->getAccount()->getId();
        }
    }

    public function fetch($id)
    {
        return $this->cache->fetch($this->getKey($id));
    }

    public function contains($id)
    {
        return $this->cache->contains($this->getKey($id));
    }

    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cache->save($this->getKey($id), $data, ($lifeTime ? $lifeTime : $this->defaultLifetime));
    }

    public function delete($id)
    {
        return $this->cache->delete($this->getKey($id));
    }
}
