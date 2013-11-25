<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

class NullFilter implements FilterInterface
{
    public function filter($text)
    {
        return $text;
    }
}
