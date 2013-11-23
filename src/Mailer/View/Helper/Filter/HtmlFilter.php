<?php

namespace Mailer\View\Helper\Filter;

use Mailer\Core\AbstractContainerAware;
use Mailer\View\Helper\FilterInterface;

class HtmlFilter extends AbstractContainerAware implements FilterInterface
{
    public function filter($text)
    {
        // @todo Remove images
        // @todo Remove JS
        // @todo Fix faulty HTML
        // @todo Remove custom script tags
        // @todo Remove swf objects and replace them per a on click spawn
        return $text;
    }
}
