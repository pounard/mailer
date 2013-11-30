<?php

namespace Mailer\Dispatch;

interface ResponseInterface
{
    /**
     * Send response to output stream
     *
     * @param string $output
     *   Computed output from the renderer
     * @param string $contentType
     *   If specific the content type the response must set in headers
     * @param int $statusCode
     *   Return status code
     * @param string $statusMessage
     *   Status message if any
     */
    public function send(
        $output,
        $contentType   = null,
        $statusCode    = null, 
        $statusMessage = null);
}
