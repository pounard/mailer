<?php

namespace Mailer\Model;

use Mailer\Mime\Multipart;

/**
 * Represents a single mail.
 */
class Mail extends Envelope
{
    /**
     * @var Multipart
     */
    private $structure;

    public function findPartFirst($type = null, $subtype = null)
    {
        
    }

    public function findPartAll($type = null, $subtype = null)
    {
        
    }

    /**
     * Get body as plain text if available
     *
     * @return string
     */
    public function getBodyPlain($escaped = false)
    {
        return '';
        if (!empty($this->bodyPlain) && $escaped) {
            // Temporary code
            $filter = new \Mailer\View\Helper\FilterCollection(array(
                new \Mailer\View\Helper\Filter\HtmlEncode(),
                new \Mailer\View\Helper\Filter\AutoParagraph(),
                new \Mailer\View\Helper\Filter\UrlToLink(),
            ));
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
        return '';
        return $this->bodyHtml;
    }

    /**
     * Get summary from plain text version of mail
     *
     * @return string
     */
    public function getSummary()
    {
        return '';
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
            'bodyPlain'         => '', //$this->bodyPlain,
            'bodyHtml'          => '', //$this->bodyHtml,
            'bodyPlainFiltered' => $this->getBodyPlain(true),
            'bodyHtmlFiltered'  => $this->getBodyHtml(true),
        );

        return $array;
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $array += array(
            'bodyPlain' => '',
            'bodyHtml'  => '',
        );

        $this->bodyPlain = $array['bodyPlain'];
        $this->bodyHtml  = $array['bodyHtml'];
    }
}
