<?php

namespace Mailer\Dispatch;

class DefaultResponse implements ResponseInterface
{
    public function send($output)
    {
        echo $output;
    }
}
