<?php

namespace Mailer\Error;

/**
 * 405
 */
class MethodNotAllowedError extends LogicError
{
    public function getStatusCode()
    {
        return 405;
    }

    public function getDefaultMessage()
    {
        return "Method Not Allowed";
    }
}
