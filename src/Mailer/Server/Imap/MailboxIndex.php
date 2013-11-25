<?php

namespace Mailer\Server\Imap;

use Mailer\Core\AbstractContainerAware;
use Mailer\Error\NotImplementedError;
use Mailer\Model\Folder;

/**
 * Mailbox index
 */
class MailboxIndex
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Folder
     */
    private $instance;

    /**
     * Default constructor
     *
     * @param Index $index
     * @param string $name
     */
    public function __construct(Index $index, $name)
    {
        $this->index = $index;
        $this->name = $name;
    }

    /**
     * Get parent instance
     *
     * @return Index
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get mailbox name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get serializable model instance of this mailbox
     *
     * @return Folder
     */
    public function getInstance($refresh = false)
    {
        if (null === $this->instance) {
            $this->instance = $this
                ->index
                ->getMailReader()
                ->getFolder($this->name);
        }

        return $this->instance;
    }

    /**
     * Get children folder flat list
     *
     * @return Folder
     */
    public function getChildren($onlySubscribed = true, $refresh = false)
    {
        return $this
            ->getIndex()
            ->getMailReader()
            ->getFolderMap(
                $this->name,
                $onlySubscribed
            );
    }

    /**
     * Updates current index
     *
     * @param \DateTime $since
     *   Date from which this should be updated
     *   If none given then rebild the full index
     */
    public function update(\DateTime $since = null)
    {
        throw new NotImplementedError();
    }

    /**
     * Get single mail
     *
     * @param int $uid
     *
     * @return Mail
     */
    public function getMail($uid, $refresh = false)
    {
        return $this
            ->getIndex()
            ->getMailReader()
            ->getMail($this->name, $uid);
    }

    /**
     * Get list of mails
     *
     * @param int[] $idList
     *
     * @return Mail[]
     */
    public function getMails(array $idList, $refresh = false)
    {
        return $this
            ->getIndex()
            ->getMailReader()
            ->getMails($this->name, $idList);
    }

    /**
     * Get single thread
     *
     * @param int $uid
     *
     * @return Thread
     */
    public function getThread($uid, $refresh = false)
    {
        throw new NotImplementedError();
    }

    /**
     * Get list of threads
     *
     * @param int[] $idList
     * @param int $limit
     * @param int $offset
     * @param int $sort
     * @param int $order
     *
     * @return Thread[]
     */
    public function getThreads(Query $query, $refresh = false)
    {
        throw new NotImplementedError();
    }

    /**
     * Get all thread mails
     *
     * @param int $uid
     * @param int $limit
     * @param int $offset
     * @param int $sort
     * @param int $order
     *
     * @return Mail[]
     */
    public function getThreadMails($uid, Query $query, $refresh = false)
    {
        throw new NotImplementedError();
    }
}
