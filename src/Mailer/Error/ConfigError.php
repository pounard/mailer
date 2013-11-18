<?php

namespace Mailer\Error;

/**
 * Configuration error
 */
class ConfigError extends \RuntimeException implements Error
{
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null === $code) {
            $code = 500;
        }

        if ($message !== null) {
            sprintf("(%d) %s", $code, $message);
        } else {
            sprintf("(%d) Configuration error", $code);
        }

        parent::__construct($message, $code, $previous);
    }
}
