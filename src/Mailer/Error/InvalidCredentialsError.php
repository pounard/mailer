<?php

namespace Mailer\Error;

/**
 * Generic business logic error
 */
class InvalidCredentialsError extends \RuntimeException implements Error
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

        if ($message === null) {
            $message = "Invalid credentials";
        }

        parent::__construct($message, $code, $previous);
    }
}
