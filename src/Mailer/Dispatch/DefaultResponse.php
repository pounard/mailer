<?php

namespace Mailer\Dispatch;

class DefaultResponse implements ResponseInterface
{
    public function send($output, $contentType = null)
    {
        echo $output;
    }
}
