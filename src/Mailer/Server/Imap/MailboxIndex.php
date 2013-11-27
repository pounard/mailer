<?php

namespace Mailer\Server\Imap;

use Mailer\Core\AbstractContainerAware;
use Mailer\Error\NotFoundError;
use Mailer\Error\NotImplementedError;
use Mailer\Mime\Charset;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Thread;

use Doctrine\Common\Cache\Cache;
use Mailer\Error\LogicError;

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
    public function __construct(
        Index $index, $name)
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
     *
     * @return Folder
     *   Updated folder instance
     */
    public function update(\DateTime $since = null)
    {
        throw new NotImplementedError();
    }

    /**
     * Get single envelope
     *
     * Use wisely this method may not be cached: in most cases it will be
     * utilized by higher level methods such as thread handling and will be
     * included in an already cached result
     *
     * In some cases this method may return Mail instances.
     *
     * @param int $uid
     *
     * @return Envelope
     */
    public function getEnvelope($uid, $refresh = false)
    {
        return reset($this->getEnvelopes(array($uid), $refresh));
    }

    /**
     * Get list of envelopes
     *
     * Use wisely this method may not be cached: in most cases it will be
     * utilized by higher level methods such as thread handling and will be
     * included in an already cached result
     *
     * In some cases this method may return Mail instances.
     *
     * @param int[] $uidList
     *
     * @return Envelope[]
     */
    public function getEnvelopes(array $uidList, $refresh = false)
    {
        return $this->index->getMailReader()->getEnvelopes($this->name, $uidList);
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
        $list = $this->getMails(array($uid), $refresh);

        return reset($list);
    }

    /**
     * Compute mail summary by adding into the mail structure to textual
     * parts
     *
     * @param Mail $mail
     */
    private function buildMail(Mail $mail)
    {
        $uid     = $mail->getUid();
        $parts   = $mail->getStructure()->findPartAll('text');
        $charset = $this->index->getContainer()->getDefaultCharset();
        $updates = array();

        foreach ($parts as $index => $part) {

            $body = $this->index->getMailReader()->getPart(
                $this->name,
                $uid,
                $index,
                $part->getEncoding()
            );

            if (!empty($body)) {
                $updates[$part->getSubtype()][] = Charset::convert(
                    $body,
                    $part->getParameter('charset', "US-ASCII"),
                    $charset
                );
            }
        }

        // Compute summary
        foreach (array('plain', 'html') as $type) {
            if (!empty($updates[$type])) { 
                foreach ($updates[$type] as $body) {
                    $updates['summary'] = $this->index->bodyFilter($body, $type, true);
                }
                break; // No need to go further (chain of responsability)
            }
        }

        foreach (array('plain' => 'bodyPlain', 'html' => 'bodyHtml') as $type => $property) {
            if (isset($updates[$type])) {
                foreach ($updates[$type] as $index => $body) {
                    $updates[$type][$index] = $this->index->bodyFilter($body, $type);
                }
                $updates[$property] = $updates[$type];
                unset($updates[$type]);
            }
        }

        if (!empty($updates)) {
            $mail->fromArray($updates);
        }
    }

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
    public function flag($uid, $flag, $toggle = true)
    {
        switch (strtolower($flag)) {

            case 'seen':
            case 'flagged':
                $this->index->getMailReader()->flagMail($this->name, $uid, $flag, $toggle);
                break;

            default:
                throw new LogicError(sprintf("Invalid flag '%s", $flag));
        }
    }

    /**
     * Get list of mails
     *
     * @param int[] $uidList
     *
     * @return Mail[]
     */
    public function getMails(array $uidList, $refresh = false)
    {
        $ret     = array();
        $missing = array();
        $cache   = $this->index->getCache();

        if (!$refresh) {
            foreach ($uidList as $uid) {
                $key = $this->index->getCacheKey('m', $uid);
                if ($mail = $cache->fetch($key)) {
                    $ret[] = $mail;
                } else {
                    $missing[] = $uid;
                }
            }
        } else {
            $missing = $uidList;
        }

        if (!empty($missing)) {
            $missing = $this->index->getMailReader()->getMails($this->name, $uidList);
            foreach ($missing as $mail) {
                $ret[] = $mail;

                // Preload any textual parts to be cached along the mail and
                // be able to compute a summary for threads
                $this->buildMail($mail);

                $key = $this->index->getCacheKey('m', $uid);
                $cache->save($key, $mail);
            }
        }

        return $ret;
    }

    /**
     * Build thread from raw data
     *
     * @param int $uid
     *   Root mail uid
     * @param int $uidMap
     *   Full uid list as returned by MailReaderInterface::getThread()
     */
    private function buildThread($uid, array $uidMap)
    {
        $first       = null;
        $last        = null;
        $firstUnread = null;
        $lastUnread  = null;
        $recent      = 0;
        $unseen      = 0;
        $persons     = array();

        foreach ($this->getEnvelopes(array_keys($uidMap)) as $envelope) {

            // @todo Make comparaison (using date or arrival) configurable
            if (null === $first || $envelope->isBefore($first)) {
                $first = $envelope;
            }
            if (null === $last || $last->isBefore($envelope)) {
                $last = $envelope;
            }

            if ($from = $envelope->getFrom()) {
                $persons[$from->getMail()] = $from;
            }
            foreach ($envelope->getTo() as $person) {
                $persons[$person->getMail()] = $person;
            }

            if (!$envelope->isSeen()) {
                ++$unseen;
                if (null === $firstUnread || $envelope->isBefore($firstUnread)) {
                    $firstUnread = $envelope;
                }
                if (null === $lastUnread || $lastUnread->isBefore($envelope)) {
                    $lastUnread = $envelope;
                }
            }
            if ($envelope->isRecent()) {
                ++$recent;
            }
        }

        if (null === $firstUnread) {
            $firstUnread = $first;
        }
        if (null === $lastUnread) {
            $lastUnread = $last;
        }

        if (!$firstUnread instanceof Mail) {
            $firstUnread = $this->getMail($firstUnread->getUid());
        }

        $thread = new Thread();
        $thread->fromArray(array(
            'uid'     => $first->getUid(),
            'subject' => $first->getSubject(),
            'summary' => $firstUnread->getSummary(),
            'created' => $first->getCreationDate(),
            'updated' => $last->getCreationDate(),
            'total'   => count($uidMap),
            'recent'  => $recent,
            'unseen'  => $unseen,
            'uidMap'  => $uidMap,
            'persons' => $persons,
            'from'    => $first->getFrom(),
            'to'      => $first->getTo(),
        ));

        return $thread;
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
        $cid = $this->index->getCacheKey('t', $uid);
        $cache = $this->index->getCache();

        if (!$refresh && ($ret = $cache->fetch($cid))) {
            return $ret;
        }

        $thread = $this->buildThread(
            $uid,
            $thread = $this
              ->getIndex()
              ->getMailReader()
              ->getThread(
                  $this->name,
                  $uid
            )
        );

        $cache->save($cid, $thread);

        return $thread;
    }

    /**
     * Get list of threads
     *
     * @param Query $query
     * @param string $refresh
     *
     * @return Thread[]
     */
    public function getThreads(Query $query = null, $refresh = false)
    {
        if (null === $query) {
            $query = new Query();
        }

        $map = $this
            ->getIndex()
            ->getMailReader()
            ->getThreads($this->name, $query);

        foreach ($map as $uid => $uidMap) {
            $map[$uid] = $this->buildThread($uid, $uidMap);
        }

        return $map;
    }

    /**
     * Get all thread mails
     *
     * Only sort and sort order will be used in the query object.
     *
     * @param int $uid
     * @param Query $query
     * @param string $refresh
     *
     * @return Mail[]
     */
    public function getThreadMails($uid, Query $query = null, $refresh = false)
    {
        if (null === $query) {
            $query = new Query();
        }

        $thread = $this->getThread($uid, $refresh);

        $mailList = $this->getMails(array_keys($thread->getUidMap()));

        // @todo
        // Apply query to what has been fetched

        return $mailList;
    }

    /**
     * Get mail part as a string
     *
     * @param int $uid
     * @param string $index
     *   Part index
     *
     * @return string
     *
    public function getPart($uid, $index)
    {
        $cid = $this->index->getCacheKey('p', $uid, $index);
        $cache = $this->index->getCache();

        if ($ret = $cache->fetch($cid)) {
            return $ret;
        }

        // We need the part for encoding and that's about all
        // we need from it: sad story is we have to do all that
        // in order to simply fetch it, I'm starting to reach the
        // limits of a too abstracted library. Hopefully this also
        // the most complex case we have to deal with.
        $part = $this->getMail($uid)->getStructure()->getPartAt($index);
        $body = $this->index->getMailReader()->getPart($uid, $index);

        if (empty($body)) {
            throw new NotFoundError("Part body is empty");
        }

        $body = Charset::convert(
            $body,
            $part->getParameter('charset', "US-ASCII"),
            $this->index->getContainer()->getDefaultCharset()
        );
    }
     */

    /**
     * Get mail part as a string
     *
     * @param int $uid
     * @param string $index
     *   Part index
     *
     * @return resource
     *
    public function getPartAsStream($uid, $index)
    {
        throw new NotImplementedError();
    }
     */
}
