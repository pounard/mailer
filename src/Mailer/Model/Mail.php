<?php

namespace Mailer\Model;

use Mailer\Mime\Multipart;
use Mailer\Mime\Part;

class Mail extends Envelope
{
    /**
     * @var Multipart
     */
    private $structure;

    /**
     * @var string
     */
    private $bodyPlain;

    /**
     * @var string
     */
    private $bodyHtml;

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
