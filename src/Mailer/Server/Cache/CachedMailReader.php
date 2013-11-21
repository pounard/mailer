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

    public function getEnvelope($name, $id)
    {
        // @todo
        return $this->nested->getEnvelope($name, $id);
    }

    public function getEnvelopes($name, array $idList)
    {
        // @todo
        return $this->nested->getEnvelopes($name, $idList);
    }

    public function getMail($name, $id)
    {
        // @todo
        return $this->nested->getMail($name, $id);
    }

    public function getMails($name, array $idList)
    {
        // @todo
        return $this->nested->getMails($name, $idList);
    }

    public function getThread($name, $id, $complete = false, $refresh = false)
    {
        // @todo
        return $this->nested->getThread($name, $id, $complete = false, $refresh);
    }

    public function getThreads(
        $name,
        $offset  = 0,
        $limit   = 100,
        $sort    = Sort::SORT_SEQ,
        $order   = Sort::ORDER_DESC,
        $refresh = false)
    {
        // @todo
        return $this->nested->getThreads($name, $offset, $limit, $sort, $order);
    }
}
