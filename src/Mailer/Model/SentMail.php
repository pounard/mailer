<?php

namespace Mailer\Model;

/**
 * This is a mail you are going to send.
 *
 * This is the only use case where you will need this.
 */
class SentMail extends Envelope
{
    /**
     * @var string
     */
    protected $bodyPlain = '';

    /**
     * @var string[]
     */
    protected $attachments = array();

    /**
     * Get plain text body
     *
     * @return string
     */
    public function getBodyPlain()
    {
        return $this->bodyPlain;
    }

    /**
     * Get file attachements
     *
     * @return string[]
     *   List of files pathes
     */
    public function getAttachements()
    {
        return $this->attachments;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'bodyPlain'   => $this->bodyPlain,
            'attachments' => $this->attachments,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->bodyPlain   = $array['bodyPlain'];
        $this->attachments = $array['attachments'];
    }
}
