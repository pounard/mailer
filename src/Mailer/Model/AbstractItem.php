<?php

namespace Mailer\Model;

abstract class AbstractItem extends AbstractContainer implements ItemInterface
{
    protected $mailbox;

    protected $uid;

    protected $subject = '';

    protected $summary = '';

    /**
     * @var Person
     */
    protected $from;

    /**
     * @var Person[]
     */
    protected $to = array();

    protected $isFlagged = false;

    protected $isDeleted = false;

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

    public function isFlagged()
    {
        return $this->isFlagged;
    }

    public function isDeleted()
    {
        return $this->isDeleted;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'mailbox'   => $this->mailbox,
            'uid'       => $this->uid,
            'subject'   => $this->subject,
            'summary'   => $this->summary,
            'from'      => $this->from,
            'to'        => $this->to,
            'isFlagged' => $this->isFlagged,
            'isDeleted' => $this->isDeleted,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->mailbox   = $array['mailbox'];
        $this->uid       = (int)$array['uid'];
        $this->subject   = $array['subject'];
        $this->summary   = $array['summary'];
        $this->from      = $array['from'];
        $this->to        = $array['to'];
        $this->isFlagged = (bool)$array['isFlagged'];
        $this->isDeleted = (bool)$array['isDeleted'];
    }
}
