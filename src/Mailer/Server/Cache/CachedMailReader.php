<?php

namespace Mailer\Server\Cache;

use Mailer\Model\Sort;
use Mailer\Server\MailReaderInterface;

/**
 * Imap server connection using the PHP IMAP extension
 */
class CachedMailReader extends AbstractCachedServerProxy implements
    MailReaderInterface
{
    public function getFolderMap(
        $parent         = null,
        $onlySubscribed = true,
        $refresh        = false)
    {
        $id = 'map';

        if ($map = $this->fetch($id)) {
            return $map;
        }

        $map = $this->nested->getFolderMap(
            $parent,
            $onlySubscribed,
            $refresh
        );

        $this->save($id, $map);

        return $map;
    }

    public function getFolder($name, $refresh = false)
    {
        $id = 'f:' . $name;

        if (!$refresh && ($ret = $this->fetch($id))) {
            return $ret;
        }

        $ret = $this->nested->getFolder($name, $refresh);

        $this->save($id, $ret);

        return $ret;
    }

    public function getThreadSummary(
        $name,
        $offset  = 0,
        $limit   = 100,
        $sort    = Sort::SORT_SEQ,
        $order   = Sort::ORDER_DESC,
        $refresh = false)
    {
        $id = 't:' . $name . ':' . $offset . ':' . $limit;

        if (!$refresh && ($ret = $this->fetch($id))) {
            return $ret;
        }

        $ret = $this->nested->getThreadSummary($name, $offset, $limit, $sort, $order);

        $this->save($id, $ret);

        return $ret;
    }
}
