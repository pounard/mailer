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
     *   Mailbox name
     * @param boolean $refresh
     *
     * @return Folder
     */
    public function getFolder($name, $refresh = false);

    /**
     * Get single mail envelope
     *
     * @param string $name
     *   Mailbox name
     * @param int $id
     *   Mail unique identifiers
     *
     * @return Envelope
     */
    public function getEnvelope($name, $id);

    /**
     * Get mails envelopes
     *
     * @param string $name
     *   Mailbox name
     * @param int[] $id
     *   List of mail unique identifiers
     *
     * @return Envelope[]
     */
    public function getEnvelopes($name, array $idList);

    /**
     * Get single mail
     *
     * @param string $name
     *   Mailbox name
     * @param int $id
     *   Mail unique identifiers
     *
     * @return Mail
     */
    public function getMail($name, $id);

    /**
     * Get mails
     *
     * @param string $name
     *   Mailbox name
     * @param int[] $id
     *   List of mail unique identifiers
     *
     * @return Mail[]
     */
    public function getMails($name, array $idList);

    /**
     * Get thread starting with the given mail unique identifier
     *
     * @param string $name
     *   Mailbox name
     * @param int $id
     *   Root message uid
     * @param boolean $complete
     *   If set to true will return complete Mail instances instead of
     *   Envelope instances in the thread
     * @param boolean $refresh
     *
     * @return Mail[]
     */
    public function getThread($name, $id, $complete = false, $refresh = false);

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
