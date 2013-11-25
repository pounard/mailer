<?php

namespace Mailer\Model;

abstract class AbstractItem implements ItemInterface
{
    protected $mailbox;

    protected $uid;

    protected $created;

    protected $updated;

    protected $total = 1;

    protected $unseen = 0;

    protected $recent = 0;

    protected $subject = '';

    protected $summary = '';

    protected $from;

    protected $to = array();

    public function getMailbox()
    {
        return $this->mailbox;
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function getCreationDate()
    {
        return $this->created;
    }

    public function getLastUpdate()
    {
        return $this->updated;
    }

    public function getMessageCount()
    {
        return $this->messageCount;
    }

    public function getUnseenCount()
    {
        return $this->unseenCount;
    }

    public function getRecentCount()
    {
        return $this->recentCount;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function toArray()
    {
        return array(
            'mailbox' => $this->mailbox,
            'uid'     => $this->uid,
            'created' => $this->created,
            'updated' => $this->updated,
            'total'   => $this->total,
            'unseen'  => $this->unseen,
            'recent'  => $this->recent,
            'subject' => $this->subject,
            'summary' => $this->summary,
            'from'    => $this->from,
            'to'      => $this->to,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        $this->mailbox = $array['mailbox'];
        $this->uid     = (int)$array['uid'];
        $this->created = $array['created'];
        $this->updated = $array['updated'];
        $this->total   = (int)$array['total'];
        $this->unseen  = (int)$array['unseen'];
        $this->recent  = (int)$array['recent'];
        $this->subject = $array['subject'];
        $this->summary = $array['summary'];
        $this->from    = $array['from'];
        $this->to      = $array['to'];
    }
}
