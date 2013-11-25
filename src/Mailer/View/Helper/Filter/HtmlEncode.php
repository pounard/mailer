<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

// PHP 5.3 compatibility
if (!defined('ENT_SUBSTITUTE')) {
    define('ENT_SUBSTITUTE', 0);
}
if (!defined('ENT_DISALLOWED')) {
    define('ENT_DISALLOWED', 0);
}

/**
 * Convert HTML special entities to their equivalents
 */
class HtmlEncode implements FilterInterface
{
    public function filter($text)
    {
        return htmlspecialchars(
            $text,
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED,
            mb_internal_encoding() // @todo Should use container
        );
    }
}
