<?php

namespace Mailer\Server\Rcube;

use Mailer\Error\NotImplementedError;
use Mailer\Model\Envelope;
use Mailer\Model\Folder;
use Mailer\Model\Mail;
use Mailer\Model\Sort;
use Mailer\Model\Thread;
use Mailer\Server\AbstractServer;
use Mailer\Server\MailReaderInterface;
use Mailer\Error\LogicError;
use Mailer\Error\NotFoundError;

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
     * Get single mail
     *
     * @param int $id
     *   Mail unique identifiers
     *
     * @return Mail
     */
    public function getMail($id)
    {
        throw new NotImplementedError();
    }

    /**
     * Get mails
     *
     * @param int[] $id
     *   List of mail unique identifiers
     *
     * @return Mail[]
     */
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
     * Get mail list from the given folder
     *
     * @param string $name
     *   Folder name
     * @param int $offset
     *   Where to start
     * @param int $limti
     *   Number of threads to fetch
     * @param int $sort
     *   Sort field
     * @param int $order
     *   Sort order
     *
     * @return Thread[]
     *   Ordered thread list
     */
    public function getThreads(
        $name,
        $offset   = 0,
        $limit    = 50,
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_DESC,
        $refresh = false)
    {
        $map = array();

        $client = $this->getClient();

        if (Sort::SORT_SEQ !== $sort) {
            throw new NotImplementedError("Only sort by sequence is implemented yet");
        }

        $threads = $client->thread($name, 'REFERENCES', '', true);

        if (Sort::ORDER_DESC === $order) {
            $threads->revert();
        }

        if ($offset !== 0 || $limit < $threads->count()) {
            $threads->slice($offset, $limit);
        }

        $tree = $threads->get_tree();

        foreach ($tree as $root) {
            // 
        }

        return $map;
    }
}
