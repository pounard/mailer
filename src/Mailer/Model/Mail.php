<?php

namespace Mailer\Model;

use Mailer\Server\Protocol\Body\Multipart;

/**
 * Represents a single mail.
 */
class Mail extends Envelope
{
    /**
     * @var Multipart
     */
    private $bodyStructure;

    /**
     * Get body structure
     *
     * @return Multipart
     */
    public function getBodyStructure()
    {
        return $this->getBodyStructure();
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array += array(
            'bodyStructure' => $this->bodyStructure,
        );

        return $array;
    }

    public function fromArray(array $array)
    {
        parent::fromArray($array);

        $array += array(
            'bodyStructure' => null,
        );

        $this->bodyStructure = $array['bodyStructure'];
    }
}
