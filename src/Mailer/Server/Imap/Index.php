<?php

namespace Mailer\Server\Imap;

use Mailer\Core\AbstractContainerAware;
use Mailer\Core\Container;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Error\NotImplementedError;
use Mailer\Model\Folder;
use Mailer\Model\SentMail;
use Mailer\Server\Smtp\MailSenderInterface;

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
     * @var MailSenderInterface
     */
    private $sender;

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
    public function __construct(
        MailReaderInterface $reader,
        MailSenderInterface $sender,
        Cache $cache = null)
    {
        $this->reader = $reader;
        $this->sender = $sender;

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
        return $this->getMailboxIndex($name)->getInstance();
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
        $map    = array();
        $key    = $this->getCacheKey('fm', (int)$onlySubscribed);
        $reader = $this->getMailReader();
        $delim  = $reader->getFolderDelimiter();

        if ($refresh || !($list = $this->cache->fetch($key))) {
            $list = $reader->getFolderMap(null, $onlySubscribed);
            $this->cache->save($key, $list);
        }

        // Sorting ensures that direct parents will always be processed
        // before their child, and thus allow us having a fail-safe
        // tree creation algorithm
        sort($list);

        foreach ($list as $name) {
          $map[$name] = $this->getMailbox($name, $refresh);
          // If parent does not exists create a pseudo folder instance that
          // does not belong to IMAP server but will help the client
          // materialize the non existing yet folder
          if ($parent = $map[$name]->getParentKey()) {
            while (!isset($map[$parent])) {
              $map[$parent] = new Folder();
              $map[$parent]->fromArray(array(
                  'path'      => $parent,
                  'parent'    => null, // @todo
                  'delimiter' => $delim,
                  'unseen'    => 0,
                  'recent'    => 0,
                  'total'     => 0,
              ));
              $parent = $map[$parent]->getParentKey();
            }
          }
        }

        return $map;
    }

    /**
     * Generate clean ready to use markup from the given string
     *
     * @param string $string
     *   Input string
     * @param string $type
     *   Filter type ("plain" or "html")
     * @param boolean $summary
     *   Set this to true if you need a summary
     * @param int $size
     *   Truncate size if summary
     *
     * @return string
     */
    public function bodyFilter($string, $type = 'plain', $summary = false, $size = 200)
    {
        if ($summary) {
            $type .= '2sum';
        }

        $string = $this
            ->getContainer()
            ->getFilterFactory()
            ->getFilter($type)
            ->filter($string);

        if ($summary && !empty($string)) {
            if ($size < strlen($string)) {
                if (preg_match('/^.{1,' . $size . '}\b/su', $string, $match)) {
                    return $match[0] . '…';
                }
            }
        }

        return $string;
    }

    /**
     * Flush caches
     */
    public function flush()
    {
        // @todo Doctrine cache cannot flush?
        // The only one on packagist is 1.x and 2.x knows how to flush...
    }

    /**
     * Send mail
     *
     * @param SentMail $mail
     *   Mail to send
     * @param string $name
     *   Where to copy the mail, defaults to configured sent mailbox
     */
    public function sendMail(SentMail $mail, $name = null)
    {
        if (null === $name) {
            $config = $this->getContainer()->getConfig();
            $name = $config['mailboxes/sent'];
        }

        $mailbox = $this->getMailboxIndex($name);

        $this->sender->sendMail($mail);

        // Then, copy it.
    }

    public function setContainer(Container $container)
    {
        parent::setContainer($container);

        $account = $container->getSession()->getAccount();

        $this->cachePrefix = sprintf("i:%s:", $account->getId());

        if ($this->reader instanceof ContainerAwareInterface) {
            $this->reader->setContainer($container);
        }
        if ($this->sender instanceof ContainerAwareInterface) {
            $this->sender->setContainer($container);
        }

        $this->reader->setCredentials(
            $account->getUsername(),
            $account->getPassword()
        );
        $this->sender->setCredentials(
            $account->getUsername(),
            $account->getPassword()
        );
    }
}
