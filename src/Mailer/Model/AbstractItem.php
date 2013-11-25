<?php

namespace Mailer\Model;

abstract class AbstractItem extends AbstractContainer implements ItemInterface
{
    protected $mailbox;

    protected $uid;

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
        $this->subject = $array['subject'];
        $this->summary = $array['summary'];
        $this->from    = $array['from'];
        $this->to      = $array['to'];
    }
}
