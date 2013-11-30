<?php

namespace Mailer\Error;

/**
 * 403
 */
class UnauthorizedError extends LogicError
{
    public function getStatusCode()
    {
        return 403;
    }

    public function getDefaultMessage()
    {
        return "Forbidden";
    }
}
