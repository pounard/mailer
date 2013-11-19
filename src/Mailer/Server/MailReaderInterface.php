<?php

namespace Mailer\Server;

use Mailer\Model\Folder;
use Mailer\Model\Sort;

/**
 * Imap server connection using the PHP IMAP extension
 */
interface MailReaderInterface extends ServerInterface
{
    /**
     * Should be the standard for most UNIX Maildir and such
     */
    const DEFAULT_DELIMITER = '.';

    /**
     * Get folder flat map
     *
     * @param string $parent
     * @param boolean $onlySubscribed
     * @param boolean $refresh
     *
     * @return Folder[]
     */
    public function getFolderMap(
        $parent         = null,
        $onlySubscribed = true,
        $refresh        = false);

    /**
     * Get a single folder
     *
     * @param string $name
     * @param boolean $refresh
     *
     * @return Folder
     */
    public function getFolder($name, $refresh = false);

    /**
     * Check for folder modification
     *
     * @param string $name
     * @param \DateTime $since
     *   Null means from the begining of time, non null means
     *   update threads since
     *
     * @return array
     *   'folder': Folder instance
     *   'threads': New threads since
     */
    //public function getThreads($name, \DateTime $since = null);

    /**
     * Get mail list from the given folder
     *
     * @param string $name
     *   Folder name
     * @param int $offset
     *   Where to start
     * @param int $limti
     *   Number of threads to fetch
     * @param int $sort
     *   Sort field
     * @param int $order
     *   Sort order
     *
     * @return Thread[]
     *   Ordered thread list
     */
    public function getThreads(
        $name,
        $offset   = 0,
        $limit    = 100,
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_DESC,
        $refresh = false);
}
