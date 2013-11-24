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
    public function getBodyPlain($escaped = false)
    {
        if (!empty($this->bodyPlain) && $escaped) {
            // Temporary code
            $filter = new \Mailer\View\Helper\Filter\PlainFilter();
            return $filter->filter($this->bodyPlain);
        } else {
            return $this->bodyPlain;
        }
    }

    /**
     * Get body as html if available
     *
     * @return string
     */
    public function getBodyHtml($escaped = false)
    {
        return $this->bodyHtml;
    }

    /**
     * Get summary from plain text version of mail
     *
     * @return string
     */
    public function getSummary()
    {
        if (!empty($this->bodyPlain)) {
            if (preg_match('/^.{1,200}\b/su', $this->bodyPlain, $match)) {
                return $match[0] . '…';
            }
        }
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array += array(
            'bodyPlain'         => $this->bodyPlain,
            'bodyHtml'          => $this->bodyHtml,
            'bodyPlainFiltered' => $this->getBodyPlain(true),
            'bodyHtmlFiltered'  => $this->getBodyHtml(true),
            'summary'           => $this->getSummary(),
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
