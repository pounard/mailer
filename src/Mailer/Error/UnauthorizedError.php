<?php

namespace Mailer\Error;

/**
 * 403
 */
class UnauthorizedError extends \RuntimeException
{
    public function __construct($message = null, $code = null, $previous = null)
    {
        if ($message === null) {
            $message = "Forbidden";
        }

        if (null === $code) {
            $code = 403;
        }

        parent::__construct($message, $code, $previous);
    }
}
