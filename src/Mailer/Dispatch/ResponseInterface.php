<?php

namespace Mailer\Dispatch;

interface ResponseInterface
{
    /**
     * Send response to output stream
     *
     * @param string $output
     */
    public function send($output);
}
