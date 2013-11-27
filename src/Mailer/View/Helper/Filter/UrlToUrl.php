<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

/**
 * Converts all URL to an [url] HTML link
 *
 * Solution found on Stack Overflow, credits goes to its author.
 *   http://stackoverflow.com/questions/6393787/php-url-to-link-with-regex
 */
class UrlToUrl implements FilterInterface
{
    public function filter($text)
    {
        return preg_replace('#(\A|[^=\]\'"a-zA-Z0-9])(http[s]?://(.+?)/[^()<>\s]+)#i', '\\1[<a href="\\2">link</a>]', $text);
    }
}
