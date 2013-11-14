<?php

namespace Mailer\Dispatch;

interface ResponsableInterface
{
    /**
     * Send response to pertinent output
     *
     * @param string $output
     */
    public function send($output);
}
