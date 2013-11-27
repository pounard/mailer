<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

/**
 * Just strip HTML and attempt to convert a few valid tags in order to keep
 * minimal formatting = @todo
 */
class Strip implements FilterInterface
{
    public function filter($text)
    {
        // @todo This is probably not binary safe...
        return strip_tags($text);
    }
}
