<?php

namespace Mailer\Dispatch\Http;

use Mailer\Core\AbstractContainerAware;
use Mailer\Dispatch\RequestInterface;
use Mailer\Dispatch\ResponseInterface;

class HttpResponse extends AbstractContainerAware implements ResponseInterface
{
    /**
     * @var string[]
     */
    protected $headers = array();

    public function sendHeaders(RequestInterface $request, $contentType = null)
    {
        if (null === $contentType) {
            $this->headers["Content-Type"] = $request->getPreferredOutputContentType();
        } else {
            $this->headers["Content-Type"] = $contentType;
        }

        // @todo Request should drive charset
        $this->headers["Content-Type"] .= '; charset=' . $this->getContainer()->getDefaultCharset();

        foreach ($this->headers as $name => $value) {
            header($name . ':' . $value);
        }
    }

    public function sendContent($output)
    {
        if (!empty($output)) {
            echo $output;
        }
    }

    public function closeResponse()
    {
        // Code from Symfony 2.0 HttpFoundation component.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else if ('cli' !== PHP_SAPI) {
            // ob_get_level() never returns 0 on some Windows configurations,
            // so if the level is the same two times in a row, the loop should
            // be stopped.
            $previous = null;
            $obStatus = ob_get_status(1);
            while (($level = ob_get_level()) > 0 && $level !== $previous) {
                $previous = $level;
                if ($obStatus[$level - 1]) {
                    if (version_compare(PHP_VERSION, '5.4', '>=')) {
                        if (isset($obStatus[$level - 1]['flags']) && ($obStatus[$level - 1]['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) {
                            ob_end_flush();
                        }
                    } else {
                        if (isset($obStatus[$level - 1]['del']) && $obStatus[$level - 1]['del']) {
                            ob_end_flush();
                        }
                    }
                }
            }
            flush();
        }
    }

    public function send(RequestInterface $request, $output, $contentType = null)
    {
        $this->sendHeaders($request, $contentType);
        $this->sendContent($output);
        $this->closeResponse();
    }
}
