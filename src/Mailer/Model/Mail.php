<?php

namespace Mailer\Model;

/**
 * Represents a single mail.
 *
            [subject] => charges locataire/proprio
            [from] => Cath <g.cath@free.fr>
            [to] => pounard@processus.org
            [date] => Wed, 30 Jun 2010 07:10:32 +0200
            [message_id] => <201006300710.32339.g.cath@free.fr>
        references : la référence sur l'id de ce message
        in_reply_to : la réponse à cet identifiant de message
            [size] => 82871
            [uid] => 50
            [msgno] => 1
            [recent] => 0
            [flagged] => 0
            [answered] => 1
            [deleted] => 0
            [seen] => 1
            [draft] => 0
        [udate] => 1277874904
 */
class Mail implements ExchangeInterface
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
    public function repliesTo()
    {
        return $this->replyTo;
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
     * Is message read
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
        $this->draft      = (bool)$array['draft'];
    }
}
