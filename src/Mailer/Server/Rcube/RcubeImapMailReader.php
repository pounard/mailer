<?php

namespace Mailer\Server\Rcube;

use Mailer\Error\LogicError;
use Mailer\Error\NotImplementedError;
use Mailer\Error\NotFoundError;
use Mailer\Model\DateHelper;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Person;
use Mailer\Model\Sort;
use Mailer\Model\Thread;
use Mailer\Server\AbstractServer;
use Mailer\Server\MailReaderInterface;
use Mailer\Server\Protocol\Body\Multipart;
use Mailer\Server\Protocol\Body\Part;

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
     * @param boolean $refresh
     *
     * @return Folder[]
     */
    public function getFolderMap(
        $parent         = null,
        $onlySubscribed = true,
        $refresh        = false)
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
     * @param boolean $refresh
     *
     * @return Folder
     */
    public function getFolder($name, $refresh = false)
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

    public function getMails($name, array $idList)
    {
        $client = $this->getClient();
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
                    function (Part $part) use ($client, $uid, $name) {

                        // Slice of code from Roundcube client
                        $index = $part->getIndex();
                        $body = $client->handlePartBody(
                            $name,
                            $uid,
                            true,
                            isset($index) ? $index : 'TEXT', // FIXME Always an int
                            $part->getEncoding()
                        );

                        // convert charset (if text or message part)
                        $parameters = $part->getParameters();
                        // @todo Default charset fallback is stupid
                        $charset = isset($parameters['charset']) ? strtoupper($parameters['charset']) : 'US-ASCCI';
                        if ($body && preg_match('/^(text|message)$/', $part->getType())) {
                            // Remove NULL characters if any (#1486189)
                            if (strpos($body, "\x00") !== false) {
                                $body = str_replace("\x00", '', $body);
                            }
                            if ('US-ASCII' === $charset) {
                                // try to extract charset information from HTML meta tag (#1488125)
                                if ('html' === $part->getSubtype() && preg_match('/<meta[^>]+charset=([a-z0-9-_]+)/i', $body, $m)) {
                                    $charset = strtoupper($m[1]);
                                } /* else { @todo Where does this default charset comes from?
                                    $charset = $this->default_charset;
                                } */
                            }
                            $body = \rcube_charset::convert($body, $charset);
                        }

                        return $body;
                    }
                );

                foreach ($multipart as $part) {
                    print_r($part->getContents());
                    echo "\n\n\n";
                }
                die();

                $array['bodyStructure'] = $multipart;

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
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_ASC,
        $refresh = false)
    {
        $map = array();

        $client = $this->getClient();

        if (Sort::SORT_SEQ !== $sort) {
            throw new NotImplementedError("Only sort by sequence is implemented yet");
        }

        $threads = @$client->thread($name, 'REFERENCES', '', true);

        if (Sort::ORDER_DESC === $order) {
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

        return $map;
    }

    /**
     * Build thread info from envelopes
     *
     * This is the heart of this module's UI
     *
     * @param Envelope[] $envelopes
     *
     * @return array
     */
    private function buildThreadArray(array $envelopes)
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
        //$mail = $this->getMail($name, $id);

        return array(
            'subject'      => $first->getSubject(),
            'summary'      => null, // @todo
            'persons'      => $personMap,
            'startedDate'  => $first->getDate(),
            'lastUpdate'   => $last->getDate(),
            'messageCount' => $total,
            'recentCount'  => $recent,
            'unseenCount'  => $unseen,
            'uidMap'       => $uidMap,
        ); 
    }

    public function getThread($name, $id, $complete = false, $refresh = false)
    {
        // Ordering by ASC will avoid calling the reverse() operation on
        // roundcube thread returned instance
        $tree = $this->getPartialThreads($name, 0, null, Sort::SORT_SEQ, Sort::ORDER_ASC, $refresh);

        foreach ($tree as $root => $uidList) {

            if ((int)$root !== (int)$id) {
                continue;
            }

            if ($complete) {
                $list = $this->getMails($name, $uidList);
            } else {
                $list = $this->getEnvelopes($name, $uidList);
            }

            $list = array_filter($list, function ($envelope) {
                return !$envelope->isDeleted();
            });

            break;
        }

        if (!isset($list)) {
            throw new NotFoundError("Could not find thread");
        }

        return $list;
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
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_DESC,
        $refresh = false)
    {
        $tree = $this->getPartialThreads($name, $offset, $limit, $sort, $order, $refresh);

        foreach ($tree as $root => $uidList) {

            $envelopes = array_filter($this->getEnvelopes($name, $uidList), function ($envelope) {
                return !$envelope->isDeleted();
            });

            if (empty($envelopes)) { // Everything has been deleted
                unset($tree[$root]);
                continue;
            }

            $thread = new Thread();
            $thread->fromArray(array('id' => $root) + $this->buildThreadArray($envelopes));

            $tree[$root] = $thread;
        }

        return $tree;
    }
}
