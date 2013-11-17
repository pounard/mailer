<?php

namespace Mailer\Server\Cache;

use Mailer\Model\Sort;
use Mailer\Server\MailReaderInterface;

/**
 * Imap server connection using the PHP IMAP extension
 */
class CachedMailReader extends AbstractServerProxy implements
    MailReaderInterface
{
    public function getFolderMap(
        $parent         = null,
        $onlySubscribed = true,
        $refresh        = false)
    {
        return $this->nested->getFolderMap(
            $parent,
            $onlySubscribed,
            $refresh
        );
    }

    public function getFolder($name, $refresh = false)
    {
        return $this->nested->getFolder($name, $refresh);
    }

    public function getThreadSummary(
        $name,
        $offset   = 0,
        $limit    = 100,
        $sort     = Sort::SORT_SEQ,
        $order    = Sort::ORDER_DESC)
    {
        return $this->nested->getThreadSummary(
            $name,
            $offset,
            $limit,
            $sort,
            $order
        );
    }
}
