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
    private $mailbox;

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
     * Reply to header
     *
     * @var string
     */
    private $replyTo;

    /**
     * Server identifier of message this message replies to
     *
     * @var string
     */
    private $inReplyTo;

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
    private $seen;

    /**
     * @var boolean
     */
    private $draft;

    /**
     * Get mailbox name
     *
     * @return string
     */
    public function getMailbox($string)
    {
        return $this->mailbox;
    }

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
     * Get reply to header value
     *
     * @return string
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Return server identifier this message replies to
     *
     * @return string
     */
    public function getInReplyto()
    {
        return $this->inReplyTo;
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

    /**
     * Does this envelope has been sent or arrived before the other one
     *
     * @param Envelope $envelope
     * @param string $useArrival
     *
     * @return boolean
     */
    public function isBefore(Envelope $envelope, $useArrival = false)
    {
        if (null === $this->date) {
            return $this->uid < $envelope->getUid();
        }

        $date = $envelope->getDate();

        if (null === $date) {
            return $this->uid < $envelope->getUid();
        }

        return $this->date < $date;
    }

    public function toArray()
    {
        return array(
            'mailbox'    => $this->mailbox,
            'subject'    => $this->subject,
            'from'       => $this->from,
            'to'         => $this->to,
            'date'       => $this->date,
            'id'         => $this->id,
            'references' => $this->references,
            'replyTo'    => $this->replyTo,
            'inReplyTo'  => $this->inReplyTo,
            'size'       => $this->size,
            'uid'        => $this->uid,
            'num'        => $this->num,
            'recent'     => $this->recent,
            'flagged'    => $this->flagged,
            'answered'   => $this->answered,
            'deleted'    => $this->deleted,
            'seen'       => $this->seen,
            'draft'      => $this->draft,
        );
    }

    public function fromArray(array $array)
    {
        $array += array(
            'mailbox'    => '',
            'subject'    => '',
            'from'       => null,
            'to'         => null,
            'date'       => null,
            'id'         => -1,
            'references' => null,
            'replyTo'    => null,
            'inReplyTo'  => null,
            'size'       => -1,
            'uid'        => -1,
            'num'        => -1,
            'recent'     => false,
            'flagged'    => false,
            'answered'   => false,
            'deleted'    => false,
            'seen'       => true,
            'draft'      => false,
        );

        $this->mailbox    = $array['mailbox'];
        $this->subject    = $array['subject'];
        $this->from       = $array['from'];
        $this->to         = $array['to'];
        $this->date       = $array['date'];
        $this->id         = $array['id'];
        $this->references = $array['references'];
        $this->replyTo    = $array['replyTo'];
        $this->inReplyTo  = $array['inReplyTo'];
        $this->size       = (int)$array['size'];
        $this->uid        = (int)$array['uid'];
        $this->num        = (int)$array['num'];
        $this->recent     = (bool)$array['recent'];
        $this->flagged    = (bool)$array['flagged'];
        $this->answered   = (bool)$array['answered'];
        $this->deleted    = (bool)$array['deleted'];
        $this->seen       = (bool)$array['seen'];
        $this->draft      = (bool)$array['draft'];
    }
}
