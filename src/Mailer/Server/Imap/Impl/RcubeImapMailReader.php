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
     * @var \rcube_imap_generic
     */
    private $client;

    /**
     * Default destructor
     */
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
        return $isSecure ? MailReaderInterface::PORT_SECURE : MailReaderInterface::PORT;
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
     * Get folder delimiter
     *
     * @return string
     */
    public function getFolderDelimiter()
    {
        return @$this->getClient()->getHierarchyDelimiter();
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
        $client = $this->getClient();

        $ref = $parent ? $parent : '';

        if ($onlySubscribed) {
            $data = @$client->listSubscribed($ref, name);
        } else {
            $data = @$client->listMailboxes($ref, $name);
        }

        return $data;
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

    public function purgeFolder($name, array $uidList = null)
    {
        $client = $this->getClient();

        if (false === @$client->expunge($mailbox, $uidList)) {
            throw new LogicError(sprintf("IMAP error: %d %s", $client->errornum, $client->error));
        }
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
            'to'         => Person::fromMailAddress(@$header->get('to')), // FIXME
            'cc'         => Person::fromMailAddress(@$header->get('cc')), // FIXME
            'bcc'        => Person::fromMailAddress(@$header->get('bcc')), // FIXME
            'created'    => DateHelper::fromRfc2822(@$header->get('date')),
            'encoding'   => @$header->encoding,
            'charset'    => @$header->charset,
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
                    $array['structure'] = Multipart::createInstanceFromArray($bodyStructure);
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

    public function getMailSource($name, $uid, $charset = null, $maxBytes = 0)
    {
        return @$this
            ->getClient()
            ->handlePartBody($name, $uid, true, null, null, null, null, true, $maxBytes);
    }

    public function saveMail(Mail $mail, array $headers)
    {
        /*
         *     public function save_message($folder, &$message, $headers='', $is_file=false, $flags = array(), $date = null, $binary = false)
    {
        if (!strlen($folder)) {
            $folder = $this->folder;
        }

        if (!$this->check_connection()) {
            return false;
        }

        // make sure folder exists
        if (!$this->folder_exists($folder)) {
            return false;
        }

        $date = $this->date_format($date);

        if ($is_file) {
            $saved = $this->conn->appendFromFile($folder, $message, $headers, $flags, $date, $binary);
        }
        else {
            $saved = $this->conn->append($folder, $message, $flags, $date, $binary);
        }

        if ($saved) {
            // increase messagecount of the target folder
            $this->set_messagecount($folder, 'ALL', 1);
        }

        return $saved;
    }

        $this->getClient()->save_message($store_target, $msg, $headers, $mailbody_file ? true : false, array('SEEN'));
         */
    }

    public function flagMail($name, $uid, $flag, $toggle = true)
    {
        $client = $this->getClient();

        if ($toggle) {
            $ret = @$client->flag($name, array($uid), $flag);
        } else {
            $ret = @$client->unflag($name, array($uid), $flag);
        }

        if (false === $ret) {
            throw new LogicError(sprintf("IMAP error: %d %s", $client->errornum, $client->error));
        }
    }

    public function moveMail($name, $uid, $destName)
    {
        $this->getClient()->move(array($uid), $name, $destName);
    }

    public function deleteMail($name, $uid)
    {
        $this->flagMail($name, $uid, 'deleted', true);
    }

    public function getPart($name, $uid, $index, $encoding = null)
    {
        if (Part::INDEX_ROOT === $index) {
            $index = 'TEXT';
        }

        return @$this->getClient()->handlePartBody($name, $uid, true, $index, $encoding);
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
                foreach ($this->flattenTree($value, $key) as $k => $v) {
                    $ret[$k] = $v;
                }
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

        throw new NotFoundError(sprintf("Thread '%d' does not exist in folder '%s'", $uid, $name));
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
        $threads = @$client->thread($name, 'REFS', '', true);

        if ($threads->is_empty()) {
            return $map;
        }

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
