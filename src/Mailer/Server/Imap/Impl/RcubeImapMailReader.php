<?php

namespace Mailer\Server\Imap\Impl;

use Mailer\Error\LogicError;
use Mailer\Error\NotImplementedError;
use Mailer\Error\NotFoundError;
use Mailer\Mime\Multipart;
use Mailer\Mime\Part;
use Mailer\Model\DateHelper;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Person;
use Mailer\Model\Thread;
use Mailer\Server\AbstractServer;
use Mailer\Server\Imap\MailReaderInterface;
use Mailer\Server\Imap\Query;

class RcubeImapMailReader extends AbstractServer implements
    MailReaderInterface
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
     * @var \rcube_imap_generic
     */
    private $client;

    public function __destruct()
    {
        if (null !== $this->client && $this->client->connected()) {
            $this->client->closeConnection();
        }
    }

    /**
     * Get client, proceed to the connection if not connected
     *
     * @return \rcube_imap_generic
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = new \rcube_imap_generic();
        }

        if (!$this->client->connected()) {

            $options = array(
                'port'      => $this->getPort(),
                'ssl_mode'  => $this->isSecure() ? 'ssl' : null, // @todo TLS?
                'timeout'   => 5,
                'auth_type' => null, // Best supported one
            );

            $success = @$this->client->connect(
                $this->getHost(),
                $this->getUsername(),
                $this->getPassword(),
                $options
            );

            if (!$success) {
                throw new LogicError(
                    "Could not connect to IMAP server: " . $this->client->error,
                    $this->client->errornum
                );
            }
        }

        return $this->client;
    }

    public function getDefaultPort($isSecure)
    {
        return $isSecure ? self::PORT_SECURE : self::PORT;
    }

    public function isConnected()
    {
        return null !== $this->client && $this->client->connected();
    }

    public function connect()
    {
        try {
            $this->getClient();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get folder flat map
     *
     * @param string $parent
     * @param boolean $onlySubscribed
     *
     * @return Folder[]
     */
    public function getFolderMap($parent = null, $onlySubscribed = true)
    {
        $map = array();

        $client = $this->getClient();
        $delim  = @$this->client->getHierarchyDelimiter();
        $ref    = $parent ? $parent : '';
        $name   = '';

        if ($onlySubscribed) {
            $data = @$client->listSubscribed($ref, name);
        } else {
            $data = @$client->listMailboxes($ref, $name);
        }

        // Sorting ensures that direct parents will always be processed
        // before their child, and thus allow us having a fail-safe
        // tree creation algorithm
        sort($data);

        foreach ($data as $name) {
            $map[$name] = $this->getFolder($name);
            // If parent does not exists create a pseudo folder instance that
            // does not belong to IMAP server but will help the client
            // materialize the non existing yet folder
            if ($parent = $map[$name]->getParentKey()) {
                while (!isset($map[$parent])) {
                    $map[$parent] = new Folder();
                    $map[$parent]->fromArray(array(
                        'path'         => $parent,
                        'parent'       => null, // @todo
                        'delimiter'    => $delim,
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

    /**
     * Get a single folder
     *
     * @param string $name
     *
     * @return Folder
     */
    public function getFolder($name)
    {
        $client = $this->getClient();

        if (false === ($total = @$client->countMessages($name))) {
            throw new NotFoundError(sprintf("Folder '%s' does not exists", $name));
        }

        $data = array(
            'path'         => $name,
            'delimiter'    => @$client->getHierarchyDelimiter(),
            'messageCount' => $total,
            'recentCount'  => @$client->countRecent($name),
            'unseenCount'  => @$client->countUnseen($name),
        );

        $folder = new Folder();
        $folder->fromArray($data);

        return $folder;
    }

    /**
     * Build envelope array from header
     *
     * @param \rcube_message_header $header
     *   Header
     * @param string $name
     *   Mailbox name
     *
     * @return array
     */
    private function buildEnvelopeArray(\rcube_message_header $header, $name = null)
    {
        return array(
            'mailbox'    => $name,
            'subject'    => @$header->get('subject'), // @todo
            'from'       => Person::fromMailAddress(@$header->get('from')), // @todo
            'to'         => Person::fromMailAddress(@$header->get('to')), // @todo
            'date'       => DateHelper::fromRfc2822(@$header->get('date')), // @todo
            'id'         => @$header->messageID,
            'references' => @$header->get('references'),
            'replyTo'    => @$header->get('replyto'),
            'inReplyTo'  => @$header->get('in_reply_to'),
            'size'       => @$header->get('size'),
            'uid'        => @$header->uid,
            'num'        => @$header->id,
            'recent'     => isset($header->flags['RECENT']),
            'flagged'    => isset($header->flags['FLAGGED']),
            'answered'   => isset($header->flags['ANSWERED']),
            'deleted'    => isset($header->flags['DELETED']),
            'seen'       => isset($header->flags['SEEN']),
            'draft'      => isset($header->flags['DRAFT']),
        );
    }

    /**
     * Build envelope array from header
     *
     * @param \rcube_message_header $header
     *   Header
     * @param string $name
     *   Mailbox name
     *
     * @return array
     */
    private function buildMailArray(\rcube_message_header $header, $name = null)
    {
        $ret = $this->buildEnvelopeArray($header, $name);

        return $ret;
    }

    public function getEnvelope($name, $id)
    {
        $envelopes = $this->getEnvelopes($name, array($id));

        if (!empty($envelopes)) {
            return array_shift($envelopes);
        }

        throw new NotFoundError("Mail not found");
    }

    public function getEnvelopes($name, array $idList)
    {
        $ret = @$this->getClient()->fetchHeaders($name, $idList, true);

        if (false === $ret) {
            throw new NotFoundError("Mailbox or mail(s) have not been found");
        }

        foreach ($ret as $index => $header) {
            if ($header instanceof \rcube_message_header) {
                $envelope = new Envelope();
                $envelope->fromArray($this->buildEnvelopeArray($header, $name));
                $ret[$index] = $envelope;
            } else {
                // This should never happen only doing this for autocompletion
                unset($ret[$index]);
            }
        }

        return $ret;
    }

    public function getMail($name, $id)
    {
        $mails = $this->getMails($name, array($id));

        if (!empty($mails)) {
            return array_shift($mails);
        }

        throw new NotFoundError("Mail not found");
    }

    /**
     * Find and set body into given array
     *
     * @param Multipart $multipart
     * @param unknown $array
     */
    private function findBody(Multipart $multipart, &$array)
    {
        if (isset($array['bodyHtml']) && isset($array['bodyPlain'])) {
            return;
        }

        foreach ($multipart as $part) {
            if ($part instanceof Multipart) {
                $this->findBody($part, $array);
            } else if ('text' === $part->getType()) {
                if (empty($array['bodyHtml']) && false !== strpos($part->getSubtype(), 'html')) {
                    $array['bodyHtml'] = $part->getContents();
                } if (empty($array['bodyPlain']) && false !== strpos($part->getSubtype(), 'plain')) {
                    $array['bodyPlain'] = $part->getContents();
                }
            }
        }
    }

    /**
     * Clean fetched body
     *
     * @param string $body
     * @param string $charset
     */
    public function cleanBody($body, $type, $subtype, $charset = 'US-ASCII')
    {
        // Remove NULL characters if any (#1486189)
        if (strpos($body, "\x00") !== false) {
            $body = str_replace("\x00", '', $body);
        }
        if ('US-ASCII' === $charset && preg_match('/^(text|message)$/', $type)) {
            // try to extract charset information from HTML meta tag (#1488125)
            if (false !== strpos($subtype, 'html') && preg_match('/<meta[^>]+charset=([a-z0-9-_]+)/i', $body, $matches)) {
                $charset = strtoupper($matches[1]);
            }
        }

        return @\rcube_charset::convert($body, $charset, $this->getContainer()->getDefaultCharset());
    }

    public function getMails($name, array $idList)
    {
        $client = $this->getClient();
        $self = $this;
        $ret = @$client->fetchHeaders($name, $idList, true);

        if (false === $ret) {
            throw new NotFoundError("Mailbox or mail(s) have not been found");
        }

        foreach ($ret as $index => $header) {
            if ($header instanceof \rcube_message_header) {

                $array = $this->buildMailArray($header, $name);
                $uid = $array['uid'];

                // Roundcube in certain cases seems to be able to drop the body
                // structure raw array here, I never happened to fall into this
                // case but their own code takes it into account, for safety
                // let's do it too
                if (empty($header->bodystructure)) {
                    $bodyStructure = @$client->getStructure($name, $uid, true);
                } else {
                    $bodyStructure = $header->bodystructure;
                }

                if (empty($bodyStructure)) {
                    trigger_error(sprintf("Mail with uid '%s' has no body structure", $uid));
                    unset($ret[$index]);
                    continue; // Invalid mail
                }

                $multipart = Multipart::createInstanceFromArray(
                    $bodyStructure,
                    function (Part $part) use ($client, $uid, $name, $self) {

                        $index = $part->getIndex();
                        $index = ($index === Part::INDEX_ROOT ? 'TEXT' : $index);
                        $body  = @$client->handlePartBody($name, $uid, true, $index, $part->getEncoding());

                        if (empty($body)) { // Can have no body there.
                            return false;
                        } else {
                            return $self->cleanBody($body, $part->getType(), $part->getSubtype(), strtoupper($part->getParameter('charset')));
                        }
                    }
                );

                $this->findBody($multipart, $array);

                $mail = new Mail();
                $mail->fromArray($array);
                $ret[$index] = $mail;
            } else {
                // This should never happen only doing this for autocompletion
                unset($ret[$index]);
            }
        }

        return $ret;
    }

    /**
     * Alias of getThreads() that don't load envelopes
     *
     * @return array
     */
    private function getPartialThreads(
        $name,
        $offset   = 0,
        $limit    = 50,
        $sort     = Query::SORT_SEQ,
        $order    = Query::ORDER_ASC)
    {
        $map = array();

        $client = $this->getClient();


        $threads = @$client->thread($name, 'REFERENCES', '', true);

        if (Query::ORDER_DESC === $order) {
            $threads->revert();
        }

        if ($offset !== 0 || ($limit && $limit < $threads->count())) {
            $threads->slice($offset, $limit);
        }

        $tree = $threads->get_tree();

        foreach ($tree as $root => $values) {
            $uidList = $this->flattenTree($values);
            $uidList[] = $root;
            $uidList = array_unique($uidList, SORT_NUMERIC);

            $map[$root] = $uidList;
        }

        $this->orderPartialThreads($map);

        return $tree;
    }

    /**
     * Order partial thread tree
     *
     * @param array $tree
     *   Output of the getPartialThreads() method
     */
    private function orderPartialThreads(&$tree)
    {
        // In most cases sorting by sequence or arrival is the same: please
        // keep in mind this function will be used for basic UI in most cases
        // and not for complex queries
        if (Query::SORT_SEQ !== $sort && Query::SORT_ARRIVAL !== $sort && Query::SORT_DATE !== $sort) {
            throw new NotImplementedError("Only sort by sequence is implemented yet");
        }
    }

    /**
     * Build thread info from envelopes
     *
     * This is the heart of this module's UI
     *
     * @param string $name
     * @param Envelope[] $envelopes
     *
     * @return array
     */
    private function buildThreadArray($name, array $envelopes)
    {
        $first       = null;
        $last        = null;
        $firstUnread = null;
        $lastUnread  = null;
        $personMap   = array();
        $uidMap      = array();
        $recent      = 0;
        $unseen      = 0;
        $total       = count($envelopes);

        foreach ($envelopes as $envelope) {

            // @todo Make comparaison (using date or arrival) configurable
            if (null === $first || $envelope->isBefore($first)) {
                $first = $envelope;
            }
            if (null === $last || $last->isBefore($envelope)) {
                $last = $envelope;
            }

            $from = $envelope->getFrom();
            $personMap[$from->getMail()] = $from;
            $to = $envelope->getTo();
            $personMap[$to->getMail()] = $to;

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

            // @todo Uid?
            $uidMap[$envelope->getUid()] = $envelope->getInReplyto(); 
        }

        if (null === $firstUnread) {
            $firstUnread = $first;
        }
        if (null === $lastUnread) {
            $lastUnread = $last;
        }

        // @todo Make summary selection configurable
        $mail = $this->getMail($name, $firstUnread->getUid());

        return array(
            'subject'      => $first->getSubject(),
            'summary'      => $mail->getSummary(), // @todo
            'persons'      => $personMap,
            'startedDate'  => $first->getDate(),
            'lastUpdate'   => $last->getDate(),
            'messageCount' => $total,
            'recentCount'  => $recent,
            'unseenCount'  => $unseen,
            'uidMap'       => $uidMap,
        ); 
    }

    public function getThread(
        $name,
        $id,
        $order = Query::ORDER_ASC,
        $complete = false)
    {
        $tree = $this->getPartialThreads($name, 0, null, Query::SORT_SEQ, Query::ORDER_ASC);

        foreach ($tree as $root => $uidList) {

            if ((int)$root !== (int)$id) {
                continue;
            }

            if ($complete) {
                $list = $this->getMails($name, $uidList);
            } else {
                $list = $this->getEnvelopes($name, $uidList);
            }

            if (Query::ORDER_DESC === $order) {
                $list = array_reverse($list);
            }

            $list = array_filter($list, function ($envelope) {
                return !$envelope->isDeleted();
            });

            return $list;
        }

        throw new NotFoundError("Could not find thread");
    }

    /**
     * Flatten the given tree
     *
     * @param array $tree
     *
     * @return $map
     */
    private function flattenTree($tree)
    {
        $ret = array();

        foreach ($tree as $key => $value) {
            $ret[] = $key;
            if (!empty($value)) {
                $ret = array_merge($ret, $this->flattenTree($value));
            }
        }

        return $ret;
    }

    public function getThreads(
        $name,
        $offset   = 0,
        $limit    = 50,
        $sort     = Query::SORT_SEQ,
        $order    = Query::ORDER_DESC)
    {
        $tree = $this->getPartialThreads($name, $offset, $limit, $sort, $order);

        foreach ($tree as $root => $uidList) {

            $envelopes = array_filter($this->getEnvelopes($name, $uidList), function ($envelope) {
                return !$envelope->isDeleted();
            });

            if (empty($envelopes)) { // Everything has been deleted
                unset($tree[$root]);
                continue;
            }

            $thread = new Thread();
            $thread->fromArray(array('id' => $root) + $this->buildThreadArray($name, $envelopes));

            $tree[$root] = $thread;
        }

        return $tree;
    }
}