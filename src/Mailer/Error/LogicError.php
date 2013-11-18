<?php

namespace Mailer\Error;

/**
 * Generic business logic error
 */
class LogicError extends \RuntimeException implements Error
{
    public function getStatusCode()
    {
        return 500;
    }

    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null === $code) {
            $code = $this->getStatusCode();
        }

        if ($message !== null) {
            sprintf("(%d) %s", $code, $message);
        } else {
            sprintf("(%d) Error", $code);
        }

        parent::__construct($message, $code, $previous);
    }
}
