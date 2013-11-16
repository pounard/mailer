<?php

namespace Mailer\Dispatch\Http;

use Mailer\Dispatch\ResponseInterface;

class HttpResponse implements ResponseInterface
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
     * Specific constructor
     */
    public function __construct(HttpRequest $request = null)
    {
        if (null !== $request) {
            $this->request = $request;
        }
    }

    public function sendHeaders()
    {
        // @todo
        $this->headers += array(
            "Content-Type" => "text/html",
        );
    }

    public function sendContent($output)
    {
        echo $output;
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

    public function send($output)
    {
        $this->sendHeaders();
        $this->sendContent($output);
        $this->closeResponse();
    }
}
