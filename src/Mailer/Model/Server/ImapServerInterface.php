<?php

namespace Mailer\Model\Server;

/**
 * Imap server connection using the PHP IMAP extension
 */
interface ImapServerInterface extends ServerInterface
{
    /**
     * Get folder flat map
     *
     * @param string $parent
     * @param boolean $refresh
     *
     * @return Folder[]
     */
    public function getFolderMap($parent = null, $refresh = false);

    /**
     * Get a single folder
     *
     * @param string $name
     * @param boolean $refresh
     *
     * @return Folder
     */
    public function getFolder($name, $refresh = false);
}
