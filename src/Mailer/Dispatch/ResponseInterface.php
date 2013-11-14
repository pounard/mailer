<?php

namespace Mailer\Dispatch;

interface ResponseInterface
{
    /**
     * Send response to pertinent output
     *
     * @param string $output
     */
    public function send($output);
}
