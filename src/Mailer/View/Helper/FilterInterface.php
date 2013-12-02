<?php

namespace Mailer\View\Helper;

interface FilterInterface
{
    /**
     * Fitler text for client display
     *
     * @param string $text
     * @param string $charset
     *   Input text charset
     */
    public function filter($text, $charset = null);
}
