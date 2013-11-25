<?php

namespace Mailer\Server\Imap\Impl;

use Mailer\Error\LogicError;
use Mailer\Error\NotFoundError;
use Mailer\Mime\Multipart;
use Mailer\Mime\Part;
use Mailer\Model\DateHelper;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Person;
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
                        'path'      => $parent,
                        'parent'    => null, // @todo
                        'delimiter' => $delim,
                        'unseen'    => 0,
                        'recent'    => 0,
                        'total'     => 0,
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
            'path'      => $name,
            'delimiter' => @$client->getHierarchyDelimiter(),
            'total'     => $total,
            'recent'    => @$client->countRecent($name),
            'unseen'    => @$client->countUnseen($name),
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
            'subject'    => @$header->get('subject'),
            'from'       => Person::fromMailAddress(@$header->get('from')),
            'to'         => Person::fromMailAddress(@$header->get('to')),
            'created'    => DateHelper::fromRfc2822(@$header->get('date')),
            'id'         => @$header->messageID,
            'references' => @$header->get('references'),
            'replyTo'    => @$header->get('replyto'),
            'inReplyTo'  => @$header->get('in_reply_to'),
            'size'       => @$header->get('size'),
            'uid'        => @$header->uid,
            'seq'        => @$header->id,
            'isRecent'   => isset($header->flags['RECENT']),
            'isFlagged'  => isset($header->flags['FLAGGED']),
            'isAnswered' => isset($header->flags['ANSWERED']),
            'isDeleted'  => isset($header->flags['DELETED']),
            'isSeen'     => isset($header->flags['SEEN']),
            'isDraft'    => isset($header->flags['DRAFT']),
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

    public function getEnvelope($name, $uid)
    {
        $envelopes = $this->getEnvelopes($name, array($uid));

        if (!empty($envelopes)) {
            return array_shift($envelopes);
        }

        throw new NotFoundError("Mail not found");
    }

    public function getEnvelopes($name, array $uidList)
    {
        $ret = @$this->getClient()->fetchHeaders($name, $uidList, true);

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

        if (count($ret) !== count($uidList)) {
            throw new NotFoundError("Some mails have not been found");
        }

        return $ret;
    }

    public function getMail($name, $uid)
    {
        $mails = $this->getMails($name, array($uid));

        if (!empty($mails)) {
            return array_shift($mails);
        }

        throw new NotFoundError("Mail not found");
    }

    public function getMails($name, array $uidList)
    {
        $client = $this->getClient();
        $self = $this;
        $ret = @$client->fetchHeaders($name, $uidList, true);

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
                } else {
                    $multipart = Multipart::createInstanceFromArray($bodyStructure);

                    // Callback depends on current arguments
                    $callback  = function (Part $part) use ($client, $uid, $name, $self) {
                        $index = $part->getIndex();
                        $index = ($index === Part::INDEX_ROOT ? 'TEXT' : $index);
                        return @$client->handlePartBody($name, $uid, true, $index, $part->getEncoding());
                    };
                    // Fetch plain text and HTML body parts if found
                    if ($part = $multipart->findPartFirst('text', 'plain')) {
                        $part->setContents($callback);
                        $array['bodyPlain'] = $part->getContents();
                    }
                    if ($part = $multipart->findPartFirst('text', 'html')) {
                        $part->setContents($callback);
                        $array['bodyHtml'] = $part->getContents();
                    }
                }

                $mail = new Mail();
                $mail->fromArray($array);
                $ret[$index] = $mail;
            } else {
                // This should never happen only doing this for autocompletion
                unset($ret[$index]);
            }
        }

        if (count($ret) !== count($uidList)) {
            throw new NotFoundError("Some mails have not been found");
        }

        return $ret;
    }

    /**
     * Flatten the given tree
     *
     * @param array $tree
     *
     * @return $map
     */
    private function flattenTree($tree, $parent)
    {
        $ret = array();

        foreach ($tree as $key => $value) {
            $ret[$key] = $parent;
            if (!empty($value)) {
                $ret = array_merge($ret, $this->flattenTree($value, $key));
            }
        }

        return $ret;
    }

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
    public function getThread($name, $uid)
    {
        $threads = $this->getThreads($name, new Query(Query::LIMIT_NONE));

        if (isset($threads[$uid])) {
            return $threads[$uid];
        }

        throw new NotFoundError("Thread '%d' does not exist in folder '%s'", $uid);
    }

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
    public function getThreads($name, Query $query = null)
    {
        $map = array();

        $client  = @$this->getClient();
        $threads = @$client->thread($name, 'REFERENCES', '', true);

        if (null === $query) {
            $query = new Query();
        }
        if (Query::ORDER_DESC === $query->getOrder()) {
            $threads->revert();
        }

        $limit = $query->getLimit();
        $offset = $query->getOffset();

        if ($offset !== 0 || ($limit && $limit < @$threads->count())) {
            @$threads->slice($offset, $limit);
        }

        $tree = @$threads->get_tree();

        foreach ($tree as $root => $values) {
            $uidList = $this->flattenTree($values, $root);
            $uidList[$root] = 0;
            ksort($uidList, SORT_NUMERIC);
            $map[$root] = $uidList;
        }

        return $map;
    }
}
