<?php

namespace Mailer\Dispatch;

class DefaultResponse implements ResponseInterface
{
    public function send(RequestInterface $request, $output, $contentType = null)
    {
        if (!empty($output)) {
            echo $output;
        }
    }
}
