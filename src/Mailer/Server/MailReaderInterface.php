<?php

namespace Mailer\Server;

use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Sort;
use Mailer\Model\Thread;

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
     * Get single mail
     *
     * @param int $id
     *   Mail unique identifiers
     *
     * @return Mail
     */
    public function getMail($id);

    /**
     * Get mails
     *
     * @param int[] $id
     *   List of mail unique identifiers
     *
     * @return Mail[]
     */
    public function getMails(array $idList);

    /**
     * Get thread starting with the given mail unique identifier
     *
     * @param int $id
     * @param boolean $refresh
     *
     * @return Thread
     */
    public function getThread($id, $refresh = false);

    /**
     * Get thread mails with the given mail unique identifier
     *
     * @param int $id
     * @param boolean $refresh
     *
     * @return Mail[]
     */
    public function getThreadMails($id, $refresh = false);

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
