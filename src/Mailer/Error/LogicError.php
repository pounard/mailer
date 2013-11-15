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
        parent::__construct(
            sprintf("(%d) %s", $this->getStatusCode(), $message),
            $code,
            $previous
        );
    }
}
