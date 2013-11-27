<?php

namespace Mailer\Server\Imap;

use Mailer\Core\AbstractContainerAware;
use Mailer\Core\Container;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Error\NotImplementedError;
use Mailer\Model\Folder;
use Mailer\View\Helper\FilterCollection;
use Mailer\View\Helper\FilterInterface;

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
     * @var FilterInterface[]
     */
    private $filters = array();

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
     * @param ... parameters
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cachePrefix . ':' . implode(':', func_get_args());
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
        $key = $this->getCacheKey('fm', (int)$onlySubscribed);

        if (!$refresh && ($ret = $this->cache->fetch($key))) {
            return $ret;
        }

        $map = $this->getMailReader()->getFolderMap(null, $onlySubscribed);
        $this->cache->save($key, $map);

        return $map;
    }

    /**
     * Get configured filter for the given text subtype
     *
     * @param string $subtype
     *   Input type ("plain" or "html")
     *
     * @return FilterInterface
     */
    public function getFilter($subtype)
    {
        if (isset($this->filters[$subtype])) {
            $this->filters[$subtype];
        }

        $factory = $this->getContainer()->getFilterFactory();

        // @todo Un-hardcode those
        switch ($subtype) {

            case 'plain':
                $this->getContainer()->getFilterFactory();
                $filter = new FilterCollection(array(
                    $factory->getInstance('htmlesc'),
                    $factory->getInstance('autop'),
                    $factory->getInstance('urltoa'),
                ));
                break;

            default: // Default is to be to more secure one
            case 'html':
                // @todo: WRONG UNSECURE
                $filter = new FilterCollection(array(
                    $factory->getInstance('null'),
                ));
                break;
        }

        return $this->filters[$subtype] = $filter;
    }

    /**
     * Compute summary from given string
     *
     * @param string $string
     *   Input string
     * @param string $subtype
     *   Input type ("plain" or "html")
     *
     * @return string
     */
    public function bodyToSummary($string, $subtype = 'plain')
    {
        $body = $this->getFilter($subtype)->filter($string);

        if (!empty($body)) {
            $body = strip_tags($body);
            if (preg_match('/^.{1,200}\b/su', $body, $match)) {
                return $match[0] . '…';
            }
        }

        return null;
    }

    /**
     * Generate clean ready to use markup from the given string
     *
     * @param string $string
     *   Input string
     * @param string $subtype
     *   Input type ("plain" or "html")
     *
     * @return string
     */
    public function bodyFilter($string, $subtype = 'plain')
    {
        // @todo
        return $this->getFilter($subtype)->filter($string);
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
