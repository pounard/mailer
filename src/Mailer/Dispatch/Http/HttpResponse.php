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

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * Build the response by adding specific headers
     *
     * @param array $headers
     */
    public function __construct(HttpRequest $request, array $headers = null)
    {
        $this->request = $request;

        if (null !== $headers) {
            foreach ($headers as $name => $value) {
                $this->addHeader($name, $value);
            }
        }
    }

    /**
     * Add response header
     *
     * @param string $contentType
     *   If specific the content type the response must set in headers
     * @param int $statusCode
     *   Return status code
     * @param string $statusMessage
     *   Status message if any
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    private function sendHeaders($contentType = null, $statusCode = null, $statusMessage = null)
    {
        if (null === $statusCode) {
            $statusCode = 200;
        }
        if (null === $statusMessage) {
            switch ($statusCode) { // @todo
                case 200:
                    $statusMessage = "OK";
                    break;
                default:
                    $statusMessage = "Oups";
                    break;
            }
        }

        header(sprintf('HTTP/1.1 %s %s', $statusCode, $statusMessage), true, $statusCode);

        if (null !== $contentType) {
            $this->headers["Content-Type"] = $contentType;
            // @todo Charset should be incomming request driven
            $this->headers["Content-Type"] .= '; charset=' . $this->getContainer()->getDefaultCharset();
        }

        foreach ($this->headers as $name => $value) {
            header($name . ':' . $value);
        }
    }

    private function sendContent($output)
    {
        if (!empty($output)) {
            echo $output;
        }
    }

    private function closeResponse()
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

    public function send(
        $output,
        $contentType   = null,
        $statusCode    = null, 
        $statusMessage = null)
    {
        $this->sendHeaders($contentType, $statusCode, $statusMessage);
        $this->sendContent($output);
        $this->closeResponse();
    }
}
