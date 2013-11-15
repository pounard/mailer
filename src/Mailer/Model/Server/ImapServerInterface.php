<?php

namespace Mailer\Model\Server;

/**
 * Imap server connection using the PHP IMAP extension
 */
interface ImapServerInterface extends ServerInterface
{
    /**
     * List folders
     *
     * @param string $parent
     *
     * @return Folder[]
     */
    public function listFolders($parent = null);

    /**
     * Get a single folder
     *
     * @param string $name
     *
     * @return Folder
     */
    public function getFolder($name);
}
