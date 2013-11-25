<?php

namespace Mailer\Server\Imap;

use Mailer\Core\AbstractContainerAware;
use Mailer\Core\Container;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Error\NotImplementedError;
use Mailer\Model\Folder;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;

/**
 * IMAP protocol is stupid enough to force us to cache over it:
 * this where it starts
 */
class Index extends AbstractContainerAware
{
    /**
     * @var MailboxIndex[]
     */
    private $mailboxes = array();

    /**
     * @var MailReaderInterface
     */
    private $reader;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * Default constructor
     *
     * @param MailReaderInterface $reader
     */
    public function __construct(MailReaderInterface $reader, Cache $cache = null)
    {
        $this->reader = $reader;

        if (null === $cache) {
            $this->cache = new ArrayCache();
        } else {
            $this->cache = $cache;
        }
    }

    /**
     * Get cache key for this cache entry
     *
     * @param string $id
     * @param string $type 
     *
     * @return string
     */
    public function getCacheKey($id, $type = null)
    {
        if (null === $type) {
            return sprintf("%s:N:%s", $this->cachePrefix, $id);
        } else {
            return sprintf("%s:%s:%s", $this->cachePrefix, $type, $id);
        }
    }

    /**
     * Get internal mail reader instance
     *
     * If you are calling this method into a controller then you are doing
     * it wrong: it should never be accessed outside of the index with one
     * exception which is the IMAP auth connector.
     *
     * @return MailReaderInterface
     */
    public function getMailReader()
    {
        return $this->reader;
    }

    /**
     * Get cache
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Fetch single mailbox index
     *
     * @param string $name
     * @param boolean $refresh
     *
     * @return MailboxIndex
     */
    public function getMailboxIndex($name, $refresh = true)
    {
        if (!isset($this->mailboxes[$name])) {
            $this->mailboxes[$name] = new MailboxIndex($this, $name);
        }

        return $this->mailboxes[$name];
    }

    /**
     * Fetch single mailbox
     *
     * @param string $name
     * @param boolean $refresh
     *
     * @return Folder
     */
    public function getMailbox($name, $refresh = true)
    {
        $this->getMailboxIndex($name)->getInstance();
    }

    /**
     * Fetch complete mailbox map representation
     *
     * @param boolean $onlySubscribed
     * @param boolean $refresh
     *
     * @return Folder[]
     */
    public function getMailboxMap($onlySubscribed = true, $refresh = true)
    {
        return $this->getMailReader()->getFolderMap(null, $onlySubscribed);
    }

    /**
     * Flush caches
     */
    public function flush()
    {
        // @todo Doctrine cache cannot flush?
    }

    public function setContainer(Container $container)
    {
        parent::setContainer($container);

        $account = $container->getSession()->getAccount();

        $this->cachePrefix = sprintf("i:%s:", $account->getId());

        if ($this->reader instanceof ContainerAwareInterface) {
            $this->reader->setContainer($container);
        }

        $this->reader->setCredentials(
            $account->getUsername(),
            $account->getPassword()
        );
    }
}
