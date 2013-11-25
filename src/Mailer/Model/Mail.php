<?php

namespace Mailer\Model;

use Mailer\Error\NotImplementedError;
use Mailer\Mime\Multipart;
use Mailer\Mime\Part;

/**
 * Represents a single mail.
 */
class Mail extends Envelope
{
    /**
     * @var Multipart
     */
    private $structure;

    public function toArray()
    {
        $array = parent::toArray();

        $array += array(
            'structure' => $this->structure,
        );

        return $array;
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->structure = $array['structure'];
    }
}
