<?php

namespace Mailer\Model;

/**
 * Represents a single mail.
 */
class Envelope implements ExchangeInterface
{
    /**
     * @var string
     */
    private $subject;

    /**
     * @var Person
     */
    private $from;

    /**
     * @var Person[]
     */
    private $to;

    /**
     *@var \DateTime
     */
    private $date;

    /**
     * Server identifier
     *
     * @var string
     */
    private $id;

    /**
     * Server identifier of message this message references
     * 
     * @var string
     */
    private $references;

    /**
     * Server identifier of message this message replies to
     *
     * @var string
     */
    private $repliesTo;

    /**
     * Size in bytes
     *
     * @var int
     */
    private $size;

    /**
     * Unique identifier
     *
     * @var int
     */
    private $uid;

    /**
     * Sequence number
     *
     * @var int
     */
    private $num;

    /**
     * @var boolean
     */
    private $recent;

    /**
     * @var boolean
     */
    private $flagged;

    /**
     * @var boolean
     */
    private $answered;

    /**
     * @var boolean
     */
    private $deleted;

    /**
     * @var boolean
     */
    private $unread;

    /**
     * @var boolean
     */
    private $seen;

    /**
     * @var boolean
     */
    private $draft;

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get from
     *
     * @return Person
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get to
     *
     * @return Person[]
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get sent date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get server identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return server identifier this message refers to
     *
     * @return string
     */
    public function isReferenceTo()
    {
        return $this->references;
    }

    /**
     * Return server identifier this message replies to
     *
     * @return string
     */
    public function getRepliesToId()
    {
        return $this->repliesTo;
    }

    /**
     * Get size in bytes
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get unique identifier
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Get sequence number
     *
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Is message recent
     *
     * @return bool
     */
    public function isRecent()
    {
        return $this->recent;
    }

    /**
     * Is message flagged
     *
     * @return bool
     */
    public function isFlagged()
    {
        return $this->flagged;
    }

    /**
     * Has message been answered
     *
     * @return bool
     */
    public function isAnswered()
    {
        return $this->answered;
    }

    /**
     * Is message marked for deletion
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Is message unread
     *
     * @return boolean
     */
    public function isUnread()
    {
        return $this->unread;
    }

    /**
     * Is message seen
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->seen;
    }

    /**
     * Is message a draft
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->draft;
    }

    public function toArray()
    {
        return array(
            'subject'    => $this->subject,
            'from'       => $this->from,
            'to'         => $this->date,
            'id'         => $this->id,
            'references' => $this->references,
            'repliesTo'  => $this->repliesTo,
            'size'       => $this->size,
            'uid'        => $this->uid,
            'num'        => $this->num,
            'recent'     => $this->recent,
            'flagged'    => $this->flagged,
            'answered'   => $this->answered,
            'deleted'    => $this->deleted,
            'read'       => $this->read,
            'draft'      => $this->draft,
            'unread'     => $this->unread,
        );
    }

    public function fromArray(array $array)
    {
        $array += array(
            'subject'    => '',
            'from'       => '',
            'to'         => array(),
            'date'       => null,
            'id'         => -1,
            'references' => null,
            'repliesTo'  => null,
            'size'       => -1,
            'uid'        => -1,
            'num'        => -1,
            'recent'     => false,
            'flagged'    => false,
            'answered'   => false,
            'deleted'    => false,
            'seen'       => true,
            'unread'     => false,
            'draft'      => false,
        );

        $this->subject    = $array['subject'];
        $this->from       = $array['from'];
        $this->to         = $array['to'];
        $this->date       = $array['date'];
        $this->id         = $array['id'];
        $this->references = $array['references'];
        $this->repliesTo  = $array['repliesTo'];
        $this->size       = (int)$array['size'];
        $this->uid        = (int)$array['uid'];
        $this->num        = (int)$array['num'];
        $this->recent     = (bool)$array['recent'];
        $this->flagged    = (bool)$array['flagged'];
        $this->answered   = (bool)$array['answered'];
        $this->deleted    = (bool)$array['deleted'];
        $this->seen       = (bool)$array['seen'];
        $this->unread     = (bool)$array['unread'];
        $this->draft      = (bool)$array['draft'];
    }
}
