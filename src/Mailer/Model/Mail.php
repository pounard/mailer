<?php

namespace Mailer\Model;

use Mailer\Mime\Multipart;

class Mail extends Envelope
{
    /**
     * @var Multipart
     */
    private $structure;

    /**
     * @var string
     */
    private $bodyPlain = array();

    /**
     * @var string
     */
    private $bodyHtml = array();

    /**
     * @var string[]
     */
    private $attachments = array();

    /**
     * Get the mail structure
     *
     * @return Multipart
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Get plain text version of body if any
     *
     * This will given you an escaped and processed content; It is not real
     * from the mail; For being able to fetch the original data use the
     * structure property instead
     *
     * @return Attachment[]
     */
    public function getBodyPlain()
    {
        return $this->bodyPlain;
    }

    /**
     * Get HTML version of body if any
     *
     * This will given you an escaped and processed content; It is not real
     * from the mail; For being able to fetch the original data use the
     * structure property instead
     *
     * @return string[]
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * Get list of attached files
     *
     * This will give you a preprocessed list of file attachments
     * that cannot be modified
     *
     * @return
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'structure'   => $this->structure,
            'bodyPlain'   => $this->bodyPlain,
            'bodyHtml'    => $this->bodyHtml,
            'attachments' => $this->attachments,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->structure   = $array['structure'];
        $this->bodyPlain   = $array['bodyPlain'];
        $this->bodyHtml    = $array['bodyHtml'];
        $this->attachments = $array['attachments'];
    }
}
