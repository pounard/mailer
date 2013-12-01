<?php

namespace Mailer\Validator;

use Zend\Validator\EmailAddress as ZendEmailAddress;

/**
 * Custom mail validaor that let pass mails formatted with the full
 * name prefixed
 */
class EmailAddress extends ZendEmailAddress
{
    public function isValid($value)
    {
        $matches = array();
        if (preg_match("/[^<]*<([^>]+)>$/", trim($value), $matches)) {
            return parent::isValid($matches[1]);
        }
        return parent::isValid($value);
    }
}
