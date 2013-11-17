<?php

namespace Mailer\Model\Server\Native;

use Mailer\Error\LogicError;
use Mailer\Error\NotFoundError;
use Mailer\Error\NotImplementedError;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Person;
use Mailer\Model\Server\AbstractServer;
use Mailer\Model\Server\MailReaderInterface;
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

    public function __construct(array $options)
    {
        parent::__construct($options);

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
    protected function connect($name = null, $flags = 0)
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
            return array($matches[5], $matches[2]);
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

        $folders = imap_getmailboxes($this->connect($parent), $this->getMailboxName($parent), "*");

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
            $parent = $folder->getParentKey();
            do {
                $map[$parent] = $this->createFolderInstance($parent);
                $parent = $map[$parent]->getParentKey();
            } while (!isset($map[$parent]));
        }

        return $map;
    }

    /**
     * Create folder instance from IMAP server data
     */
    protected function createFolderInstance($path, array $data = array())
    {
        if (false !== ($pos = strrpos($path, $this->delimiter))) {
            $parent = substr($path, 0, $pos);
            $name   = substr($path, $pos + 1);
        } else {
            $parent = null;
            $name   = $path;
        }

        $data += array(
            'Date'   => null,
            'Nmsgs'  => -1,
            'Recent' => 0,
        );
        if (isset($data['Date'])) {
            $date = $this->parseDate($data['Date']);
        } else {
            $date = null;
        }

        return new Folder(
            $name,
            $path,
            $date,
            $data['Nmsgs'],
            $data['Recent'],
            $parent
        );
    }

    public function getFolder($name, $refresh = false)
    {
        if (!$data = imap_check($this->connect($name, OP_READONLY))) {
            throw new NotFoundError("Folder does not exist");
        }

        return $this->createFolderInstance($name, (array)$data);
    }

    /**
     * Fetches mail
     *
     * @param array $uidList
     * @param string $name
     *
     * @return \Mailer\Model\Mail[]
     */
    protected function getMails(array $uidList, $name = null)
    {
        $data = imap_fetch_overview(
            $this->connect($name, OP_READONLY),
            implode(",", array_unique($uidList)),
            FT_UID
        );

        array_walk($data, function (&$value) {
            $value = (array)$value;

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
                $value['repliesTo'] = (int)$value['in_reply_to'];
            }

            $mail = new Mail();
            $mail->fromArray($value);
            $value = $mail;
        });

        return $data;
    }

    /**
     * Set sort on given resource
     *
     * @param resource $resource
     * @param int $sort
     */
    protected function setSort($resource, $sort, $order = Sort::ORDER_DESC)
    {
        switch ($sort) {

            case Sort::SORT_DATE:
                $sort = SORTDATE;
                break;

            case Sort::SORT_ARRIVAL:
                $sort = SORTARRIVAL; 
                break;

            case Sort::SORT_FROM:
                $sort = SORTFROM;
                break;

            case Sort::SORT_SUBJECT:
                $sort = SORTSUBJECT;
                break;

            case Sort::SORT_TO:
                $sort = SORTTO;
                break;

            case Sort::SORT_CC:
                $sort = SORTCC;
                break;

            case Sort::SORT_SIZE:
                $sort = SORTSIZE;
                break;

            default:
            case Sort::SORT_SEQ:
                $sort = 0;
                break;
        }

        imap_sort($resource, $sort, Sort::ORDER_DESC === $order);
    }

    public function getThreadSummary(
        $name,
        $offset   = 0,
        $limit    = 100,
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_DESC)
    {
        // This implementation will trust the IMAP server thread list instead
        // of trying to rethread the messages by itself: simple things tend to
        // work better than complex ones

        $ret = array();
        $map = array();

        $resource = $this->connect($name);
        //$this->setSort($resource, $sort);

        // This will fetch the full thread information of the folder
        // @todo I'm afraid that on huge folders this will be quite slow
        $data = imap_thread($resource, SE_UID);

        foreach ($data as $key => $value) {
            list($id, $type) = explode('.', $key);

            // Build a flattened list of thread values
            $map[$id][$type] = $value;
        }

        // Rebuild correct tree
        foreach ($map as $info) {
            $ret[$info['branch']]['messages'][] = $info['num'];
        }

        // The goal is to get thread summary, start by fetching mails
        array_walk($ret, function (&$value, $id) use ($name) {

            $recentCount = 0;
            $unseenCount = 0;
            $startedDate = null;
            $lastUpdate  = null;
            $title       = null;
            $persons     = array();

            // Fetch mail information and go
            $mails = $this->getMails($value['messages'], $name);
            foreach ($mails as $mail) {

                if (null === $subject) {
                    $subject = $mail->getSubject();
                }

                $participants = $mail->getTo();
                $participants[] = $mail->getFrom();
                foreach ($participants as $person) {
                    $id = $person->getMail();
                    if (!isset($persons[$id])) {
                        $persons[$id] = $person;
                    }
                }

                $date = $mail->getDate();
                if (null !== $date) {
                    if (null === $lastUpdate || $lastUpdate < $date) {
                        $lastUpdate = $date;
                    }
                    if (null === $startedDate || $date < $startedDate) {
                        $startedDate = $date;
                    }
                }

                if ($mail->isRecent()) {
                    ++$recentCount;
                }
                if (!$mail->isSeen()) {
                    ++$unseenCount;
                }
            }

            $value = new Thread();
            $value->fromArray(array(
                'id'           => $id,
                'subject'      => $subject,
                'summary'      => null, // @todo
                'persons'      => array_values($persons),
                'startedDate'  => $startedDate,
                'lastUpdate'   => $lastUpdate,
                'messageCount' => count($mails),
                'recentCount'  => $recentCount,
                'unseenCount'  => $unseenCount,
            ));
        });

        return $ret;
    }
}
