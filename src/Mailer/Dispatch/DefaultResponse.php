<?php

namespace Mailer\Dispatch;

class DefaultResponse implements ResponseInterface
{
    public function send(
        $output,
        $contentType   = null,
        $statusCode    = null, 
        $statusMessage = null)
    {
        if (!empty($output)) {
            echo $output;
        }
        exit($statusCode);
    }
}
