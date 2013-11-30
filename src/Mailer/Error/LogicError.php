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

    public function getDefaultMessage()
    {
        return "Error";
    }

    public function __construct($message = null, $code = null, $previous = null)
    {
        if (null === $code) {
            $code = $this->getStatusCode();
        }
        if (null === $message) {
            $message = $this->getDefaultMessage();
        }
        $message = sprintf("(%d) %s", $code, $message);

        parent::__construct($message, $code, $previous);
    }
}
