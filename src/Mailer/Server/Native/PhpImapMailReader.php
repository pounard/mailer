<?php

namespace Mailer\Server\Native;

use Mailer\Error\LogicError;
use Mailer\Error\NotFoundError;
use Mailer\Error\NotImplementedError;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Person;
use Mailer\Server\AbstractServer;
use Mailer\Server\MailReaderInterface;
use Mailer\Model\Sort;
use Mailer\Model\Thread;

/**
 * Mail reader implementation using the PHP IMAP extension
 *
 * PHP IMAP extension seems quite primitive and does not provide us a very
 * helpful IMAP protocol implementation, but it will fit right for a start.
 *
 * Other alternatives have been introspected:
 *
 *   - Horde framework IMAP client: probably the best one in pure PHP that
 *     exists out there (and the fastest too); Nevertheless it has 2 huge
 *     disadvantadges:
 *
 *       - Is not really documented, and that's very sad;
 *
 *       - Their IMAP implementation is highly coupled to the Horde complete
 *         framework and will only work accompagned with at least 10 other
 *         Horde packages, including their own cache handler.
 *
 *     I wont use a dozer to kill a fly.
 *
 *   - Roundcube implementation: very fast and quite complete IMAP client
 *     implementation, but is tied to the Roundcubemail software: nearly
 *     impossible to decouple. Trail abandonned.
 *
 *   - Zend mail component: very basic and primitive IMAP client, serves
 *     no purpose in its current state: IMAP PHP extension is fare more
 *     advanced in many ways.
 *
 *   - Writing our own: very stupid thing to do for a proof-of-concept piece
 *     of software, it would engage us in writing much more code than the
 *     application itself.
 *
 * I'm sad but I have to use the PHP IMAP extension as a start.
 *
 * Note that this implementation is uncached
 * @todo Write a proxy implementation of MailReaderInterface for caching
 */
class PhpImapMailReader extends AbstractServer implements MailReaderInterface
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
     * @var string
     */
    private $delimiter = MailReaderInterface::DEFAULT_DELIMITER;

    /**
     * Imap connection handles
     *
     * @var resource
     */
    private $resource;

    /**
     * Current connected folder
     *
     * @var string
     */
    private $currentFolder;

    /**
     * Current flags
     *
     * @var int
     */
    private $currentFlags = 0;

    /**
     * Encoding used for the client
     *
     * @var string
     */
    private $encoding;

    public function __construct()
    {
        if (isset($options['encoding'])) {
            $this->encoding = $options['encoding'];
        } else {
            $this->encoding = mb_internal_encoding();
        }
    }

    /**
     * Ensures resources are closed on destruct
     */
    public function __destruct()
    {
        if ($this->resource) {
            imap_close($this->resource);
        }
        unset($this->currentFolder, $this->resource);
    }

    /**
     * Get PHP IMAP extension mailbox/connection string for the given folder
     *
     * If name is null it will give the connection string for an half connection
     *
     * @param string $name
     *
     * @return string
     */
    protected function getMailboxName($name = null)
    {
        $mailbox = "{" . $this->getHost() . ":" . $this->getPort() . "/imap";
        if ($this->isSecure()) {
            $mailbox .= "/ssl";
            if ($this->acceptsInvalidCertificate()) {
                $mailbox .= "/novalidate-cert";
            }
        }
        $mailbox .= "}";
        if (null !== $name) {
            $mailbox .= $this->encodeUtf7($name);
        }

        return $mailbox;
    }

    public function connect()
    {
        if (!$this->isConnected()) {
            try {
                $this->getResource();
            } catch (\Exception $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Connect or reconnect to given mailbox, or open an half connection
     *
     * This method will always try to reuse existing stream and avoid
     * duplicates in order to be the most efficient it can
     *
     * @param string $name
     *   Folder name or null of an half connection
     * @param int $flags
     *   Binary flag of IMAP PHP contants
     *
     * @return resource
     */
    protected function getResource($name = null, $flags = 0)
    {
        $realFlags = $flags;

        if (!$this->resource) {
            if (null === $name) {
                $realFlags |= OP_HALFOPEN;
            }
            $this->resource = imap_open(
                $this->getMailboxName($name),
                $this->getUsername(),
                $this->getPassword(),
                $realFlags
            );
        } else if ($this->currentFolder !== $name || $this->currentFlags !== $flags) {
            if (null === $name) {
                $realFlags |= OP_HALFOPEN;
            }
            $status = imap_reopen(
                $this->resource,
                $this->getMailboxName($name),
                $realFlags
            );
            if (!$status) {
                $this->resource = false;
            }
        } else {
            // Short circuit if connection maildir is already the same folder
            // with the same flags
            return $this->resource;
        }

        if (!$this->resource) {
            unset($this->currentFolder, $this->currentFlags, $this->resource);
            throw new LogicError("Could not connect to host");
        }

        $this->currentFlags = $flags;
        $this->currentFolder = $name;

        return $this->resource;
    }

    public function getDefaultPort($isSecure)
    {
        return $isSecure ? self::PORT_SECURE : self::PORT;
    }

    public function isConnected()
    {
        return isset($this->resource) && false !== $this->resource;
    }

    /**
     * Parse (pseudo) RFC2822 date
     *
     * It looks like IMAP servers append the timezone while PHP
     * won't parse it and error saying "Trailing chars".
     *
     * @param string $dateString
     *   RFC2822 date string
     *
     * @return \DateTime
     *   DateTime instance with the timezone optionnaly set or null if date
     *   could not be parsed
     */
    protected function parseDate($dateString)
    {
        if (strpos($dateString, " (")) {
            list($dateString, $timezone) = explode(" (", $dateString);
            if ($pos = strpos($timezone, ")")) {
                $timezone = substr($timezone, 0, $pos);
            }
            $timezone = new \DateTimeZone($timezone);
        } else {
            $timezone = null;
        }

        if (null === $timezone) {
            $date = \DateTime::createFromFormat(\DateTime::RFC2822, $dateString);
        } else {
            $date = \DateTime::createFromFormat(\DateTime::RFC2822, $dateString, $timezone);
        }

        if ($date) {
          return $date;
        }
    }

    /**
     * Decode string
     */
    protected function decodeMime($string)
    {
        return mb_decode_mimeheader($string);
    }

    /**
     * Decode string
     */
    protected function decodeUtf7($string)
    {
        return mb_convert_encoding($string, "UTF7-IMAP", $this->encoding);
    }

    /**
     * Encode string
     */
    protected function encodeMime($string)
    {
        return mb_encode_mimeheader($string, $this->encoding, 'Q');
    }

    /**
     * Encode string
     */
    protected function encodeUtf7($string)
    {
        return mb_convert_encoding($string, $this->encoding, "UTF7-IMAP");
    }

    /**
     * Decode mail
     */
    protected function decodeMail($mailString)
    {
        $matches = array();
        $mailString = $this->decodeMime($mailString);
        if (preg_match('/^(|")([^\<]*)(|")(|\<([^\>]*)\>)$/', trim($mailString), $matches)) {
            if (!empty($matches[5])) { // There is something weird there...
                return array($matches[5], $matches[2]);
            } else {
                return array($matches[0], null);
            }
        } else {
            return array($mailString, null);
        }
    }

    /**
     * Encode mail
     */
    protected function encodeMail($mail, $name = null)
    {
        return $this->encodeMime($name . " <" . $mail . ">");
    }

    /**
     * Create folder instance from IMAP server data
     */
    protected function createFolderFromData($data = array())
    {
        $data = (array)$data;

        // @todo Path compute in there
        $data['delimiter'] = $this->delimiter;

        if (false !== ($pos = strrpos($data['path'], $this->delimiter))) {
            $data['parent'] = substr($data['path'], 0, $pos);
            $data['name']   = substr($data['path'], $pos + 1);
        } else {
            $data['parent'] = null;
            $data['name']   = $data['path'];
        }

        $data += array(
            'Date'   => null,
            'Nmsgs'  => -1,
            'Recent' => 0,
        );
        $data['messageCount'] = $data['Nmsgs'];
        $data['recentCount'] = $data['Recent'];
        if (isset($data['Date'])) {
            $data['lastUpdate'] = $this->parseDate($data['Date']);
        } else {
            $data['lastUpdate'] = null;
        }

        $folder = new Folder();
        $folder->fromArray($data);

        return $folder;
    }

    /**
     * Create envelope instance from IMAP server data
     *
     * @param \stdClass $data
     *
     * @return Envelope
     */
    protected function createEnvelopeFromData($data)
    {
        $value = (array)$data;

        if (isset($value['message_id'])) {
            $value['id'] = $this->decodeMime($value['message_id']);
        }
        if (isset($value['subject'])) {
            $value['subject'] = $this->decodeMime($value['subject']);
        }
        if (isset($value['from'])) {
            list($mail, $pname) = $this->decodeMail($value['from']);
            $value['from'] = new Person($mail, $pname);
        }
        if (isset($value['to'])) {
            list($mail, $pname) = $this->decodeMail($value['to']);
            $value['to'] = array(new Person($value['to']));
        }
        if (isset($value['date'])) {
            $value['date'] = $this->parseDate($value['date']);
        }
        if (isset($value['msgno'])) {
            $value['num'] = (int)$value['msgno'];
        }
        if (isset($value['in_reply_to'])) {
            $value['inReplyTo'] = $value['in_reply_to'];
        }

        $ret = new Envelope();
        $ret->fromArray($value);

        return $ret;
    }

    public function getFolderMap(
        $parent         = null,
        $onlySubscribed = true,
        $refresh        = false)
    {
        // Forcing refresh will be ignored from here since that implementation
        // is supposed to fetch info directly from the IMAP server and do no
        // caching. Note that $parent parameter will be ignored too

        $map = array();
        $folder = null;

        $folders = imap_getmailboxes($this->getResource($parent), $this->getMailboxName($parent), "*");

        // Sorting ensures that direct parents will always be processed
        // before their child, and thus allow us having a fail-safe
        // tree creation algorithm
        uasort($folders, function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        foreach ($folders as $data) {
            $name = null;

            // Removed connection string from folder name
            if (false !== ($pos = strrpos($data->name, "}"))) {
                $name = substr($data->name, $pos + 1);
            } else {
                $name = $data->name;
            }

            $name   = $this->decodeUtf7($name);
            $folder = $this->getFolder($name);

            $map[$name] = $folder;

            // If parent does not exists create a pseudo folder instance that
            // does not belong to IMAP server but will help the client
            // materialize the non existing yet folder
            if ($parent = $folder->getParentKey()) {
                while (!isset($map[$parent])) {
                    $map[$parent] = $this->createFolderFromData(array(
                        'path' => $parent,
                        'unseenCount'  => 0,
                        'recentCount'  => 0,
                        'messageCount' => 0,
                    ));
                    $parent = $map[$parent]->getParentKey();
                }
            }
        }

        return $map;
    }

    public function getFolder($name, $refresh = false)
    {
        $resource = $this->getResource($name, OP_READONLY);

        if (!$data = imap_check($resource)) {
            throw new NotFoundError("Folder does not exist");
        }

        // Now we need to populate the unread message count
        if (false !== ($found = imap_search($resource, 'UNSEEN'))) {
            $data->unseenCount = count($found);
        } else {
            $data->unseenCount = 0; 
        }

        $data->path = $name;

        return $this->createFolderFromData($data);
    }

    public function getMail($id)
    {
        throw new NotImplementedError();
    }

    public function getMails(array $idList)
    {
        throw new NotImplementedError();
    }

    /**
     * Get thread starting with the given mail unique identifier
     *
     * @param int $id
     * @param boolean $refresh
     *
     * @return Thread
     */
    public function getThread($id, $refresh = false)
    {
        throw new NotImplementedError();
    }

    /**
     * Get thread mails with the given mail unique identifier
     *
     * @param int $id
     * @param boolean $refresh
     *
     * @return Mail[]
     */
    public function getThreadMails($id, $refresh = false)
    {
        throw new NotImplementedError();
    }

    /**
     * Really not proud of this one (TM).
     *
     * @param Envelope $node
     * @param \stdClass $context
     */
    protected function flattenTree(Envelope $node, $context)
    {
        if (!$node->isSeen()) {
            ++$context->unseen;
        }
        if ($node->isRecent()) {
            ++$context->recent;
        }
        $context->map[$node->getUid()] = isset($node->parentUid) ? $node->parentUid : null;
        if (!isset($context->date) || $context->date < $node->getDate()) {
            $context->date = $node->getDate();
        }
        $from = $node->getFrom();
        $mail = $from->getMail();
        if (!isset($context->persons[$mail])) {
            $context->persons[$mail] = $from;
        }

        foreach ($node->children as $child) {
            $this->flattenTree($child, $context);
        }
    }

    public function getThreads(
        $name,
        $offset   = 0,
        $limit    = 100,
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_DESC,
        $refresh = false)
    {
        // This implementation will trust the IMAP server thread list instead
        // of trying to rethread the messages by itself: simple things tend to
        // work better than complex ones

        $ret = array();
        $map = array();

        $resource = $this->getResource($name);

        if ($sort !== SORT::SORT_SEQ) {
            throw new NotImplementedError("Only sorting with sequence number is supported yet");
        }

        $folder = $this->getFolder($name, $refresh);
        $total = $folder->getMessageCount();

        $tree = array();
        $map = array();
        $orphans = array();

        if ($total < $offset) {
            // Desc or asc this goes out of range
            return array();
        }
        if ($total < $offset + $limit) {
            // It serves no purpose trying to fetch something
            // that don't exists and I don't want IMAP to raise
            // errors on us
            $limit = $total - $offset;
        }

        if ($order === Sort::ORDER_DESC) {
            $max = $total - $offset;
            $min = $max - $limit;
        } else {
            $min = 1;
            $max = $limit;
        }

        // Fetch and buil envelopes tree.
        $list = imap_fetch_overview($resource, $min . ':' . $max);
        foreach ($list as $data) {

            $node = $this->createEnvelopeFromData($data);
            $node->children = array();
            $map[$node->getId()] = $node;

            if ($id = $node->getInReplyto()) {
                if (isset($map[$id])) {
                    $parent = $map[$id];
                    $parent->children[] = $node;
                    $node->parentUid = $parent->getUid(); // Sorry...
                } else {
                    $orphans[] = $node;
                }
            } else {
                $tree[$node->getUid()] = $node;
            }
        }

        // Make the orphans being in the tree
        // FIXME: This will cause imprecisions if the parent are
        // on another page, we will loose the parenting
        if (!empty($orphans)) {
            foreach ($orphans as $node) {
                $tree[$node->getUid()] = $node;
            }
        }
        // Free some memory we don't need those anymore
        unset($orphans, $map);

        // FIXME: Items should already be sorted except that we
        // appended orphans, causing some kind of trouble because
        // we can only sort using sequence (hoping that uid will
        // be in sync with sequence): we can remove this sort once
        // the orphan problem is solved
        if (Sort::ORDER_DESC === $sort) {
            krsort($tree);
        } else {
            ksort($tree);
        }

        // Now we can create each thread instances
        foreach ($tree as $node) {

            $context = new \stdClass();
            $context->unseen  = 0;
            $context->recent  = 0;
            $context->map     = array();
            $context->date    = null;
            $context->persons = array();

            $this->flattenTree($node, $context);

            $thread = new Thread();
            $thread->fromArray(array(
                'id'           => $node->getUid(),
                'subject'      => $node->getSubject(),
                'summary'      => null, // @todo
                'persons'      => $context->persons,
                'startedDate'  => $node->getDate(),
                'lastUpdate'   => $context->date,
                'messageCount' => count($context->map),
                'recentCount'  => $context->recent,
                'unseenCount'  => $context->unseen,
                'uidMap'       => $context->map,
            ));

            $ret[] = $thread;
        }

        return $ret;
    }
}
