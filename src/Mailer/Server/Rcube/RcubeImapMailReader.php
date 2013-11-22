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

    /**
     * Build message part object
     *
     * @param array $part
     * @param int $count
     * @param string $parent
     */
    protected function structure_part($part, $count=0, $parent='', $mime_headers=null)
    {
        $struct = new \rcube_message_part;
        $struct->mime_id = empty($parent) ? (string)$count : "$parent.$count";

        // multipart
        if (is_array($part[0])) {
            $struct->ctype_primary = 'multipart';

        /* RFC3501: BODYSTRUCTURE fields of multipart part
          part1 array
          part2 array
          part3 array
          ....
          1. subtype
          2. parameters (optional)
          3. description (optional)
          4. language (optional)
          5. location (optional)
         */

            // find first non-array entry
            for ($i=1; $i<count($part); $i++) {
                if (!is_array($part[$i])) {
                    $struct->ctype_secondary = strtolower($part[$i]);
                    break;
                }
            }

            $struct->mimetype = 'multipart/'.$struct->ctype_secondary;

            // build parts list for headers pre-fetching
            for ($i=0; $i<count($part); $i++) {
                if (!is_array($part[$i])) {
                    break;
                }
                // fetch message headers if message/rfc822
                // or named part (could contain Content-Location header)
                if (!is_array($part[$i][0])) {
                    $tmp_part_id = $struct->mime_id ? $struct->mime_id.'.'.($i+1) : $i+1;
                    if (strtolower($part[$i][0]) == 'message' && strtolower($part[$i][1]) == 'rfc822') {
                        $mime_part_headers[] = $tmp_part_id;
                    }
                    else if (in_array('name', (array)$part[$i][2]) && empty($part[$i][3])) {
                        $mime_part_headers[] = $tmp_part_id;
                    }
                }
            }

            // pre-fetch headers of all parts (in one command for better performance)
            // @TODO: we could do this before _structure_part() call, to fetch
            // headers for parts on all levels
            if (!empty($mime_part_headers)) {
                $mime_part_headers = $this->conn->fetchMIMEHeaders($this->folder,
                    $this->msg_uid, $mime_part_headers);
            } else {
                $mime_part_headers = array();
            }

            $struct->parts = array();
            for ($i=0, $count=0; $i<count($part); $i++) {
                if (!is_array($part[$i])) {
                    break;
                }
                $tmp_part_id = $struct->mime_id ? $struct->mime_id.'.'.($i+1) : $i+1;
                $struct->parts[] = $this->structure_part($part[$i], ++$count, $struct->mime_id,
                    $mime_part_headers[$tmp_part_id]);
            }

            return $struct;
        }

        /* RFC3501: BODYSTRUCTURE fields of non-multipart part
          0. type
          1. subtype
          2. parameters
          3. id
          4. description
          5. encoding
          6. size
          -- text
          7. lines
          -- message/rfc822
          7. envelope structure
          8. body structure
          9. lines
          --
          x. md5 (optional)
          x. disposition (optional)
          x. language (optional)
          x. location (optional)
         */

        // regular part
        $struct->ctype_primary = strtolower($part[0]);
        $struct->ctype_secondary = strtolower($part[1]);
        $struct->mimetype = $struct->ctype_primary.'/'.$struct->ctype_secondary;

        // read content type parameters
        if (is_array($part[2])) {
            $struct->ctype_parameters = array();
            for ($i=0; $i<count($part[2]); $i+=2) {
                $struct->ctype_parameters[strtolower($part[2][$i])] = $part[2][$i+1];
            }

            if (isset($struct->ctype_parameters['charset'])) {
                $struct->charset = $struct->ctype_parameters['charset'];
            }
        }

        // #1487700: workaround for lack of charset in malformed structure
        if (empty($struct->charset) && !empty($mime_headers) && $mime_headers->charset) {
            $struct->charset = $mime_headers->charset;
        }

        // read content encoding
        if (!empty($part[5])) {
            $struct->encoding = strtolower($part[5]);
            $struct->headers['content-transfer-encoding'] = $struct->encoding;
        }

        // get part size
        if (!empty($part[6])) {
            $struct->size = intval($part[6]);
        }

        // read part disposition
        $di = 8;
        if ($struct->ctype_primary == 'text') {
            $di += 1;
        }
        else if ($struct->mimetype == 'message/rfc822') {
            $di += 3;
        }

        if (is_array($part[$di]) && count($part[$di]) == 2) {
            $struct->disposition = strtolower($part[$di][0]);

            if (is_array($part[$di][1])) {
                for ($n=0; $n<count($part[$di][1]); $n+=2) {
                    $struct->d_parameters[strtolower($part[$di][1][$n])] = $part[$di][1][$n+1];
                }
            }
        }

        // get message/rfc822's child-parts
        if (is_array($part[8]) && $di != 8) {
            $struct->parts = array();
            for ($i=0, $count=0; $i<count($part[8]); $i++) {
                if (!is_array($part[8][$i])) {
                    break;
                }
                $struct->parts[] = $this->structure_part($part[8][$i], ++$count, $struct->mime_id);
            }
        }

        // get part ID
        if (!empty($part[3])) {
            $struct->content_id = $part[3];
            $struct->headers['content-id'] = $part[3];

            if (empty($struct->disposition)) {
                $struct->disposition = 'inline';
            }
        }

        // fetch message headers if message/rfc822 or named part (could contain Content-Location header)
        if ($struct->ctype_primary == 'message' || ($struct->ctype_parameters['name'] && !$struct->content_id)) {
            if (empty($mime_headers)) {
                $mime_headers = $this->conn->fetchPartHeader(
                    $this->folder, $this->msg_uid, true, $struct->mime_id);
            }

            if (is_string($mime_headers)) {
                $struct->headers = \rcube_mime::parse_headers($mime_headers) + $struct->headers;
            }
            else if (is_object($mime_headers)) {
                $struct->headers = get_object_vars($mime_headers) + $struct->headers;
            }

            // get real content-type of message/rfc822
            if ($struct->mimetype == 'message/rfc822') {
                // single-part
                if (!is_array($part[8][0])) {
                    $struct->real_mimetype = strtolower($part[8][0] . '/' . $part[8][1]);
                }
                // multi-part
                else {
                    for ($n=0; $n<count($part[8]); $n++) {
                        if (!is_array($part[8][$n])) {
                            break;
                        }
                    }
                    $struct->real_mimetype = 'multipart/' . strtolower($part[8][$n]);
                }
            }

            if ($struct->ctype_primary == 'message' && empty($struct->parts)) {
                if (is_array($part[8]) && $di != 8) {
                    $struct->parts[] = $this->structure_part($part[8], ++$count, $struct->mime_id);
                }
            }
        }

        // normalize filename property
        $this->set_part_filename($struct, $mime_headers);

        return $struct;
    }


    /**
     * Set attachment filename from message part structure
     *
     * @param rcube_message_part $part Part object
     * @param string $headers Part's raw headers
     */
    protected function set_part_filename(&$part, $headers = null)
    {
        if (!empty($part->d_parameters['filename'])) {
            $filename_mime = $part->d_parameters['filename'];
        }
        else if (!empty($part->d_parameters['filename*'])) {
            $filename_encoded = $part->d_parameters['filename*'];
        }
        else if (!empty($part->ctype_parameters['name*'])) {
            $filename_encoded = $part->ctype_parameters['name*'];
        }
        // RFC2231 value continuations
        // TODO: this should be rewrited to support RFC2231 4.1 combinations
        else if (!empty($part->d_parameters['filename*0'])) {
            $i = 0;
            while (isset($part->d_parameters['filename*'.$i])) {
                $filename_mime .= $part->d_parameters['filename*'.$i];
                $i++;
            }
            // some servers (eg. dovecot-1.x) have no support for parameter value continuations
            // we must fetch and parse headers "manually"
            if ($i<2) {
                if (!$headers) {
                    $headers = $this->conn->fetchPartHeader(
                        $this->folder, $this->msg_uid, true, $part->mime_id);
                }
                $filename_mime = '';
                $i = 0;
                while (preg_match('/filename\*'.$i.'\s*=\s*"*([^"\n;]+)[";]*/', $headers, $matches)) {
                    $filename_mime .= $matches[1];
                    $i++;
                }
            }
        }
        else if (!empty($part->d_parameters['filename*0*'])) {
            $i = 0;
            while (isset($part->d_parameters['filename*'.$i.'*'])) {
                $filename_encoded .= $part->d_parameters['filename*'.$i.'*'];
                $i++;
            }
            if ($i<2) {
                if (!$headers) {
                    $headers = $this->conn->fetchPartHeader(
                            $this->folder, $this->msg_uid, true, $part->mime_id);
                }
                $filename_encoded = '';
                $i = 0; $matches = array();
                while (preg_match('/filename\*'.$i.'\*\s*=\s*"*([^"\n;]+)[";]*/', $headers, $matches)) {
                    $filename_encoded .= $matches[1];
                    $i++;
                }
            }
        }
        else if (!empty($part->ctype_parameters['name*0'])) {
            $i = 0;
            while (isset($part->ctype_parameters['name*'.$i])) {
                $filename_mime .= $part->ctype_parameters['name*'.$i];
                $i++;
            }
            if ($i<2) {
                if (!$headers) {
                    $headers = $this->conn->fetchPartHeader(
                        $this->folder, $this->msg_uid, true, $part->mime_id);
                }
                $filename_mime = '';
                $i = 0; $matches = array();
                while (preg_match('/\s+name\*'.$i.'\s*=\s*"*([^"\n;]+)[";]*/', $headers, $matches)) {
                    $filename_mime .= $matches[1];
                    $i++;
                }
            }
        }
        else if (!empty($part->ctype_parameters['name*0*'])) {
            $i = 0;
            while (isset($part->ctype_parameters['name*'.$i.'*'])) {
                $filename_encoded .= $part->ctype_parameters['name*'.$i.'*'];
                $i++;
            }
            if ($i<2) {
                if (!$headers) {
                    $headers = $this->conn->fetchPartHeader(
                        $this->folder, $this->msg_uid, true, $part->mime_id);
                }
                $filename_encoded = '';
                $i = 0; $matches = array();
                while (preg_match('/\s+name\*'.$i.'\*\s*=\s*"*([^"\n;]+)[";]*/', $headers, $matches)) {
                    $filename_encoded .= $matches[1];
                    $i++;
                }
            }
        }
        // read 'name' after rfc2231 parameters as it may contains truncated filename (from Thunderbird)
        else if (!empty($part->ctype_parameters['name'])) {
            $filename_mime = $part->ctype_parameters['name'];
        }
        // Content-Disposition
        else if (!empty($part->headers['content-description'])) {
            $filename_mime = $part->headers['content-description'];
        }
        else {
            return;
        }

        // decode filename
        if (!empty($filename_mime)) {
            if (!empty($part->charset)) {
                $charset = $part->charset;
            }
            else if (!empty($this->struct_charset)) {
                $charset = $this->struct_charset;
            }
            else {
                $charset = \rcube_charset::detect($filename_mime, $this->default_charset);
            }

            $part->filename = \rcube_mime::decode_mime_string($filename_mime, $charset);
        }
        else if (!empty($filename_encoded)) {
            // decode filename according to RFC 2231, Section 4
            if (preg_match("/^([^']*)'[^']*'(.*)$/", $filename_encoded, $fmatches)) {
                $filename_charset = $fmatches[1];
                $filename_encoded = $fmatches[2];
            }

            $part->filename = \rcube_charset::convert(urldecode($filename_encoded), $filename_charset);
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
                            $raw_msg = @$this->get_raw_body($uid);
                            $struct = \rcube_mime::parse_message($raw_msg);
                        } else {
                            return $headers;
                        }
                    }
                }
                if (empty($struct)) {
                    $struct = @$this->structure_part($structure, 0, '', $headers);
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
                $mail->fromArray($this->buildMailArray($headers, $name));
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
