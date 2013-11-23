<?php

namespace Mailer\View\Helper;

interface FilterInterface
{
    /**
     * Fitler text for client display
     *
     * @param string $text
     */
    public function filter($text);
}
