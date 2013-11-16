<?php

namespace Mailer\Model\Server;

use Mailer\Error\LogicError;
use Mailer\Model\Folder;
use Mailer\Error\NotFoundError;

/**
 * Imap server connection using the PHP IMAP extension
 */
class ImapServer extends AbstractServer implements ImapServerInterface
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
    public function getMailboxName($name = null)
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
            imap_utf7_encode($name);
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
     *
     * @return resource
     */
    public function connect($name = null)
    {
        if (!isset($this->resource)) {
            if (null === $name) {
                $resource = imap_open($this->getMailboxName($name), $this->getUsername(), $this->getPassword(), OP_HALFOPEN);
            } else {
                $resource = imap_open($this->getMailboxName($name), $this->getUsername(), $this->getPassword());
            }
        } else if ($this->currentFolder !== $this->resource) {
            if (null === $name) {
                $resource = imap_reopen($this->getMailboxName($name), OP_HALFOPEN);
            } else {
                $resource = imap_reopen($this->getMailboxName($name));
            }
        } else {
            // Short circuit if connection maildir is already the same folder
            return $this->resource;
        }

        if (false === $resource) {
            unset($this->currentFolder, $this->resource);
            throw new LogicError("Could not connect to host");
        }

        $this->resource = $resource;
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
     * "Not proud of this one" (TM)
     *
     * Recursive function for listFolders() method. I'd prefer to have a
     * recursive anonymous function but PHP cannot really do that, sadly.
     *
     * PHP is really a very very wrong language.
     */
    private function addFolder($data, &$map)
    {
        if (false !== ($pos = strrpos($data->name, "}"))) {
            $path = substr($data->name, $pos + 1);
        } else {
            $path = $data->name;
        }

        if (false !== ($pos = strrpos($path, $data->delimiter))) {
            $parent = substr($path, 0, $pos);
            $name = substr($path, $pos + 1);
        } else {
            $parent = null;
            $name = $path;
        }

        $folder = new Folder($name, $path, $parent, $data->delimiter);
        $map[$path] = $folder;

        // Our folders have been sorted by parenting order before
        if (isset($parent)) {

            if (!isset($map[$parent])) {
                // Having a parent such as "a" then a direct child such
                // as "a.b.c" without having any real "a.b." folder is
                // valid, therefore when we hit this kind of use case we
                // need to instanciate a false folder and consider it as
                // existing
                $this->addFolder((object)array(
                    'name' => $parent,
                    'delimiter' => $data->delimiter,
                ), $map);
            }

            $parent = $map[$parent];
        }
    }

    public function getFolderMap($parent = null, $refresh = false)
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
            $this->addFolder($data, $map);
        }

        return $map;
    }

    public function getFolder($name, $refresh = false)
    {
        // Forcing refresh will be ignored from here since that implementation
        // is supposed to fetch info directly from the IMAP server and do no
        // caching

        $map = $this->getFolderMap();

        if (!isset($map[$name])) {
            throw new NotFoundError(sprintf("Folder '%s' does not exist", $name));
        }

        return $map[$name];
    }

    public function getThreadList($name, $offset = 0, $limit = 100)
    {
        print_r(imap_thread($this->connect($name)));
        die();
    }
}
