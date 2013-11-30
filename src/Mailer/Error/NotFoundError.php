<?php

namespace Mailer\Error;

/**
 * 404
 */
class NotFoundError extends LogicError
{
    public function getStatusCode()
    {
        return 404;
    }

    public function getDefaultMessage()
    {
       return "Not Found";
    }
}
