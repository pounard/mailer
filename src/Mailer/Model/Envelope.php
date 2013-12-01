<?php

namespace Mailer\Model;

/**
 * Single mail headers envelope
 */
class Envelope extends AbstractItem
{
    protected $cc = array();

    protected $bcc = array();

    protected $charset = null;

    protected $encoding = null;

    protected $messageId = '';

    protected $references = '';

    protected $replyTo = '';

    protected $inReplyTo = '';

    protected $size = -1;

    protected $seq = -1;

    protected $isRecent = false;

    protected $isAnswered = false;

    protected $isSeen = true;

    protected $isDraft = false;

    public function getType()
    {
        return ItemInterface::TYPE_MAIL;
    }

    /**
     * Get CC
     *
     * @return Person[]
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Get BCC
     *
     * @return Person[]
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Get header set encoding if any
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Get header set charset if any
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get server identifier
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
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
     * Get sequence number
     *
     * @return int
     */
    public function getSequenceNum()
    {
        return $this->seq;
    }

    /**
     * Is message recent
     *
     * @return bool
     */
    public function isRecent()
    {
        return $this->isRecent;
    }

    /**
     * Has message been answered
     *
     * @return bool
     */
    public function isAnswered()
    {
        return $this->isAnswered;
    }

    /**
     * Is message seen
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->isSeen;
    }

    /**
     * Is message a draft
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->isDraft;
    }

    /**
     * Does this envelope has been sent or arrived before the other one
     *
     * @param Envelope $envelope
     *
     * @return boolean
     */
    public function isBefore(Envelope $envelope, $useArrival = false)
    {
        if (null === $this->created || null === $envelope->created) {
            return $this->uid < $envelope->uid;
        }
        return $this->created < $envelope->created;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'cc'         => $this->cc,
            'bcc'        => $this->bcc,
            'encoding'   => $this->encoding,
            'charset'    => $this->charset,
            'messageId'  => $this->messageId,
            'references' => $this->references,
            'replyTo'    => $this->replyTo,
            'inReplyTo'  => $this->inReplyTo,
            'size'       => $this->size,
            'seq'        => $this->seq,
            'isRecent'   => $this->isRecent,
            'isAnswered' => $this->isAnswered,
            'isSeen'     => $this->isSeen,
            'isDraft'    => $this->isDraft,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->cc         = $array['cc'];
        $this->bcc        = $array['bcc'];
        $this->encoding   = $array['encoding'];
        $this->charset    = $array['charset'];
        $this->messageId  = $array['messageId'];
        $this->references = $array['references'];
        $this->replyTo    = $array['replyTo'];
        $this->inReplyTo  = $array['inReplyTo'];
        $this->size       = (int)$array['size'];
        $this->seq        = (int)$array['seq'];
        $this->isRecent   = (bool)$array['isRecent'];
        $this->isAnswered = (bool)$array['isAnswered'];
        $this->isSeen     = (bool)$array['isSeen'];
        $this->isDraft    = (bool)$array['isDraft'];
    }
}
