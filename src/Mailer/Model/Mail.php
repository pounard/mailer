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

        // Special case for mail: create the body content and send it
        // into the array so that the client will get full body text
        if (null !== $this->bodyStructure) {
            foreach ($this->bodyStructure as $part) {
                if ('text' === $part->getType()) {
                    if (false !== strpos($part->getSubtype(), 'html')) {
                        $array['bodyHtml'] = $part->getContents();
                    } if (false !== strpos($part->getSubtype(), 'plain')) {
                        $array['bodyPlain'] = $part->getContents();
                    }
                }
            }
        }

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
