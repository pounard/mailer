<?php

namespace Mailer\Server\Imap;

use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Thread;
use Mailer\Server\Imap\Query;
use Mailer\Server\ServerInterface;

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
     *
     * @return Folder[]
     */
    public function getFolderMap($parent = null, $onlySubscribed = true);

    /**
     * Get a single folder
     *
     * @param string $name
     *
     * @return Folder
     */
    public function getFolder($name);

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
     *
     * @return Thread
     */
    public function getThread($name, $id);

    /**
     * Get mail list from the given folder
     *
     * Threads order should be derivated from the latest received mail and not
     * the root message date.
     *
     * @param string $name
     * @param Query $query
     *
     * @return Thread[]
     *   Ordered thread list
     */
    public function getThreads($name, Query $query = null);
}
