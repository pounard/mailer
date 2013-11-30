<?php

namespace Mailer\Error;

/**
 * When you did not implemented a feature yet, throw this
 */
class UnsupportedMediaTypeError extends \RuntimeException implements Error
{
    public function getStatusCode()
    {
        return 415;
    }

    public function getDefaultMessage()
    {
        return "Unsupported Media Type";
    }
}
