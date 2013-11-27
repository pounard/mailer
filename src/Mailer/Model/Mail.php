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
     * @return string[]
     */
    public function getBodyPlain()
    {
        return $this->bodyPlain;
    }

    /**
     * Get HTML version of body if any
     *
     * @return string[]
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array += array(
            'structure' => $this->structure,
            'bodyPlain' => $this->bodyPlain,
            'bodyHtml'  => $this->bodyHtml,
        );

        return $array;
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->structure = $array['structure'];
        $this->bodyPlain = $array['bodyPlain'];
        $this->bodyHtml  = $array['bodyHtml'];
    }
}
