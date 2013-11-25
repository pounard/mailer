<?php

namespace Mailer\Dispatch;

interface ResponseInterface
{
    /**
     * Send response to output stream
     *
     * @param RequestInterface $request
     *   Incomming request this response answers to
     * @param string $output
     *   Computed output from the renderer
     * @param string $contentType
     *   If specific the content type the response must set in headers
     */
    public function send(RequestInterface $request, $output, $contentType = null);
}
