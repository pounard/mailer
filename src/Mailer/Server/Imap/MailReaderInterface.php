<?php

namespace Mailer\Server\Imap;

use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Server\Imap\Query;
use Mailer\Server\ServerInterface;

/**
 * Imap server connection using the PHP IMAP extension
 */
interface MailReaderInterface extends ServerInterface
{
    /**
     * Default IMAP port
     */
    const PORT = 143;

    /**
     * Default IMAPS port
     */
    const PORT_SECURE = 993;

    /**
     * Should be the standard for most UNIX Maildir and such
     */
    const DEFAULT_DELIMITER = '.';

    /**
     * Get folder delimiter
     *
     * @return string
     */
    public function getFolderDelimiter();

    /**
     * Get folder flat map
     *
     * @param string $parent
     * @param boolean $onlySubscribed
     *
     * @return string[]
     *   Folder pathes
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
     * @param int $uid
     *   Mail unique identifiers
     *
     * @return Envelope
     */
    public function getEnvelope($name, $uid);

    /**
     * Get mails envelopes
     *
     * @param string $name
     *   Mailbox name
     * @param int[] $uidList
     *   List of mail unique identifiers
     *
     * @return Envelope[]
     */
    public function getEnvelopes($name, array $uidList);

    /**
     * Get single mail
     *
     * @param string $name
     *   Mailbox name
     * @param int $uid
     *   Mail unique identifier
     *
     * @return Mail
     */
    public function getMail($name, $uid);

    /**
     * Get mails
     *
     * @param string $name
     *   Mailbox name
     * @param int[] $uidList
     *   List of mail unique identifiers
     *
     * @return Mail[]
     */
    public function getMails($name, array $uidList);

    /**
     * Get mail source as plain text
     *
     * @param string $name
     *   Mailbox name
     * @param int $uid
     *   Mail unique identifier
     * @param string $charset
     *   Output charset
     * @param int $maxBytes
     *   Maximum size fetched
     *
     * @return string
     */
    public function getMailSource($name, $uid, $charset = null, $maxBytes = 0);

    /**
     * Save mail
     *
     * @param string $name
     *   Mailbox name
     * @param Mail $mail
     *   Mail the user can and has edited
     *,@param string[] $headers
     *   Because we want the backend to be the simplest possible in order to
     *   be easy to swap out, and because we want the headers to be built in
     *   a reproductible manner, the upper layer will give you this one you
     *   lucky guy!
     * @param resource $resource
     *   If mail already has been built for any reason a resource toward the
     *   fully built mime data will be given here
     *
     * @return Mail
     *   The new mail being saved
     */
    public function saveMail($name, Mail $mail, array $headers, $resource = null);

    /**
     * Flag or unflag a mail
     *
     * @param int $uid
     *   Mail unique identifier
     * @param string $flag
     *   Flag name, must be a valid IMAP flag name
     * @param string $toggle
     *   Set this to false to unflag
     */
    public function flagMail($name, $uid, $flag, $toggle = true);

    /**
     * Move mail
     *
     * @param string $name
     *   Folder name
     * @param int $uid
     *   Mail unique identifier
     * @param string $destName
     *   Destination folder name
     */
    public function moveMail($name, $uid, $destName);

    /**
     * Mark a mail for deletion
     *
     * @param string $name
     *   Mailbox name
     * @param int $uid
     *   Mail unique identifier
     */
    public function deleteMail($name, $uid);

    /**
     * Purge deleted entries from folder
     *
     * @param string $name
     *   Mailbox name
     * @param int[] $uidList
     *   Message uid list to delete, if non everything will be deleted
     */
    public function purgeFolder($name, array $uidList = null);

    /**
     * Get mail part
     *
     * @param string $name
     *   Mailbox name
     * @param int $uid
     *   Mail unique identifier
     * @param string $index
     *   Index, can be Part::INDEX_ROOT
     * @param string $encoding
     *   Part encoding if specified
     *
     * @return string
     *   Part content
     */
    public function getPart($name, $uid, $index, $encoding = null);

    /**
     * Get thread starting with the given mail unique identifier
     *
     * @param string $name
     *   Mailbox name
     * @param int $uid
     *   Root message uid
     *
     * @return int[]
     *   Keys are unique mail uids and values are associated parents
     *   Mails are sorted by uid
     */
    public function getThread($name, $uid);

    /**
     * Get mail list from the given folder
     *
     * Threads order should be derivated from the latest received mail and not
     * the root message date.
     *
     * @param string $name
     * @param Query $query
     *
     * @return int[][]
     *   Array of arrays returned by the getThread() method keyed by root node
     *   uid and ordered such as asked in the query
     */
    public function getThreads($name, Query $query = null);
}
