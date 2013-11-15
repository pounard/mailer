<?php

namespace Mailer\Model\Server;

use Mailer\Error\LogicError;
use Mailer\Model\Folder;

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
     */
    private function addFolder($data, &$map, &$ret)
    {
        $folder = null;

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

        // Our folders have been sorted by parenting order before
        if (isset($parent)) {

            if (!isset($map[$parent])) {
                // Having a parent such as "a" then a direct child such
                // as "a.b.c" without having any real "a.b." folder is
                // valid, therefore when we hit this kind of use case we
                // need to instanciate a false folder and consider it as
                // existing
                $this->addFolder(
                    (object)array(
                        'name' => $parent,
                        'delimiter' => $data->delimiter,
                    ),
                    $map,
                    $ret
                );
            }

            $parent = $map[$parent];
            $folder = new Folder($name, $parent, $data->delimiter);
            $parent->addChild($folder);

        } else {
            $folder = $ret[$name] = new Folder($name, null, $data->delimiter);
        }

        $map[$path] = $folder;
    }

    public function listFolders($parent = null)
    {
        $ret = array();
        $map = array();

        $folders = imap_getmailboxes($this->connect($parent), $this->getMailboxName($parent), "*");

        // Sorting ensures that direct parents will always be processed
        // before their child, and thus allow us having a fail-safe
        // tree creation algorithm
        uasort($folders, function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        foreach ($folders as $data) {
            $this->addFolder($data, $map, $ret);
        }

        return $ret;
    }

    public function getFolder($name)
    {
        $resource = $this->connect($name);

        // @todo
    }
}
