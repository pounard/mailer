<?php

namespace Mailer\Server\Rcube;

use Mailer\Error\NotImplementedError;
use Mailer\Error\LogicError;
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
        $flags = @$header->get('flags');
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
            'recent'     => isset($flags['RECENT']),
            'flagged'    => isset($flags['FLAGGED']),
            'answered'   => isset($flags['ANSWERED']),
            'deleted'    => isset($flags['DELETED']),
            'seen'       => isset($flags['SEEN']),
            'draft'      => isset($flags['DRAFT']),
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
     * Get charset name from message structure (first part)
     *
     * Copy/pasted from Roundcube.
     *
     * @param array $structure Message structure
     *
     * @return string Charset name
     */
    private function getStructureCharset($structure)
    {
        while (is_array($structure)) {
            if (is_array($structure[2]) && $structure[2][0] == 'charset') {
                return $structure[2][1];
            }
            $structure = $structure[0];
        }
    }

    public function getMails($name, array $idList)
    {
        $client = $this->getClient();
        $ret = @$client->fetchHeaders($name, $idList, true);

        if (false === $ret) {
            throw new NotFoundError("Mailbox or mail(s) have not been found");
        }

        foreach ($ret as $index => $headers) {
            if ($headers instanceof \rcube_message_header) {

                $array = $this->buildEnvelopeArray($headers, $name);
                $uid = $array['uid'];

                // Bunch of code from Rouncube mail client
                // All credit goes to them
                if (empty($headers->bodystructure)) {
                    $headers->bodystructure = @$client->getStructure($name, $uid, true);
                }
                $structure = $headers->bodystructure;
                if (empty($structure)) {
                    return $headers;
                }
                // Set message charset from message headers
                if ($headers->charset) {
                    $structCharset = $headers->charset;
                } else {
                    $structCharset = $this->getStructureCharset($structure);
                }
                $headers->ctype = strtolower($headers->ctype);
                // Here we can recognize malformed BODYSTRUCTURE and
                // 1. [@TODO] parse the message in other way to create our own message structure
                // 2. or just show the raw message body.
                // Example of structure for malformed MIME message:
                // ("text" "plain" NIL NIL NIL "7bit" 2154 70 NIL NIL NIL)
                if ($headers->ctype && !is_array($structure[0]) && $headers->ctype != 'text/plain'
                    && strtolower($structure[0].'/'.$structure[1]) == 'text/plain')
                {
                    // A special known case "Content-type: text" (#1488968)
                    if ($headers->ctype == 'text') {
                        $structure[1]   = 'plain';
                        $headers->ctype = 'text/plain';
                    }
                    // We can handle single-part messages, by simple fix in structure (#1486898)
                    else if (preg_match('/^(text|application)\/(.*)/', $headers->ctype, $m)) {
                        $structure[0] = $m[1];
                        $structure[1] = $m[2];
                    } else {
                        // Try to parse the message using Mail_mimeDecode package
                        // We need a better solution, Mail_mimeDecode parses message
                        // in memory, which wouldn't work for very big messages,
                        // (it uses up to 10x more memory than the message size)
                        // it's also buggy and not actively developed
                        if ($headers->size /* && rcube_utils::mem_check($headers->size * 10)*/) {
                            $raw_msg = $this->get_raw_body($uid);
                            $struct = rcube_mime::parse_message($raw_msg);
                        } else {
                            return $headers;
                        }
                    }
                }
                if (empty($struct)) {
                    $struct = $this->structure_part($structure, 0, '', $headers);
                }
                // some workarounds on simple messages...
                if (empty($struct->parts)) {
                  // ...don't trust given content-type
                  if (!empty($headers->ctype)) {
                      $struct->mime_id  = '1';
                      $struct->mimetype = strtolower($headers->ctype);
                      list($struct->ctype_primary, $struct->ctype_secondary) = explode('/', $struct->mimetype);
                  }
                  // ...and charset (there's a case described in #1488968 where invalid content-type
                  // results in invalid charset in BODYSTRUCTURE)
                  if (!empty($headers->charset) && $headers->charset != $struct->ctype_parameters['charset']) {
                      $struct->charset                     = $headers->charset;
                      $struct->ctype_parameters['charset'] = $headers->charset;
                  }
                }
                $headers->structure = $struct;

                $mail = new Mail();
                $mail->fromArray($this->buildMailArray($header, $name));
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
