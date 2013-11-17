<?php

namespace Mailer\Error;

/**
 * When you did not implemented a feature yet, throw this
 */
class NotImplementedError extends \RuntimeException implements Error
{
    public function getStatusCode()
    {
        return 500;
    }

    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null === $message) {
            $message = "Sorry, this is not implemented yet";
        }

        parent::__construct(
            sprintf("(%d) %s", $this->getStatusCode(), $message),
            $code,
            $previous
        );
    }
}
