<?php

namespace Mailer\Model;

use Mailer\Server\Protocol\Body\Multipart;

/**
 * Represents a single mail.
 */
class Mail extends Envelope
{
    /**
     * @var string
     */
    private $bodyPlain;

    /**
     * @var string
     */
    private $bodyHtml;

    /**
     * Get body as plain text if available
     *
     * @return string
     */
    public function getBodyPlain()
    {
        return $this->bodyPlain;
    }

    /**
     * Get body as html if available
     *
     * @return string
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array += array(
            'bodyPlain' => $this->bodyPlain,
            'bodyHtml'  => $this->bodyHtml,
        );

        return $array;
    }

    public function fromArray(array $array)
    {
        parent::fromArray($array);

        $array += array(
            'bodyPlain' => '',
            'bodyHtml'  => '',
        );

        $this->bodyPlain = $array['bodyPlain'];
        $this->bodyHtml  = $array['bodyHtml'];
    }
}
