<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

class StupidLinesToVoid implements FilterInterface
{
    public function filter($text, $charset = null)
    {
        // @see http://stackoverflow.com/questions/6723389/remove-repeating-character
        return preg_replace('/([-_=])\\1{4,}/', "\n", $text);
    }
}
