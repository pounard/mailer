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
        $key = 'map';

        if ($map = $this->fetch($key)) {
            return $map;
        }

        $map = $this->nested->getFolderMap(
            $parent,
            $onlySubscribed,
            $refresh
        );

        $this->save($key, $map);

        return $map;
    }

    public function getFolder($name, $refresh = false)
    {
        $key = 'f:' . $name;

        if (!$refresh && ($ret = $this->fetch($key))) {
            return $ret;
        }

        $ret = $this->nested->getFolder($name, $refresh);

        $this->save($key, $ret);

        return $ret;
    }

    public function getEnvelope($name, $id)
    {
        $key = 'e:'  . $name . ':' . $id;

        if (!$refresh && ($ret = $this->fetch($id))) {
            return $ret;
        }

        $ret = $this->nested->getEnvelope($name, $id);

        $this->save($key, $data);

        return $ret;
    }

    public function getEnvelopes($name, array $idList)
    {
        // @todo
        return $this->nested->getEnvelopes($name, $idList);
    }

    private function getMailKey($name, $id)
    {
        return 'm:'  . $name . ':' . $id;
    } 

    public function getMail($name, $id)
    {
        $key = $this->getMailKey($name, $id);

        if ($ret = $this->fetch()) {
            return $ret;
        }

        $ret = $this->nested->getMail($name, $id);

        $this->save($key, $data);

        return $ret;
    }

    public function getMails($name, array $idList)
    {
        $ret     = array();
        $missing = array();

        foreach ($idList as $id) {
            if ($ret = $this->fetch($this->getMailKey($name, $id))) {
                $ret[$id] = $ret;
            } else {
                $missing[$id] = $id;
            }
        }

        if (!empty($missing)) {
            foreach ($this->nested->getMails($name, $missing) as $mail) {
                $id = $mail->getUid();
                $ret[$id] = $mail;
                $this->save($this->getMailKey($name, $id), $mail);
            }
        }

        // @todo array_multisort()?
        return $ret;
    }

    public function getThread(
        $name,
        $id,
        $order = Sort::ORDER_ASC,
        $complete = false,
        $refresh = false)
    {
        $key = 't:' . $name . ':' . $id  . ':' . ($complete ? 1 : 0) . ':' . $order;

        if (!$refresh && ($ret = $this->fetch($key))) {
            return $ret;
        }

        $ret = $this->nested->getThread($name, $id, $order, $complete, $refresh);

        $this->save($key, $ret);

        return $ret;
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
