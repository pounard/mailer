<?php

namespace Mailer\Server\Protocol\Body;

/**
 * This interface is not RFC compliant but allows typing of multipart
 * elements
 */
interface PartInterface
{
    public function getType();

    public function getSubtype();
}
