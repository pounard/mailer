<?php

namespace Mailer\Dispatch\Http;

use Mailer\Core\AbstractContainerAware;
use Mailer\Dispatch\ResponseInterface;

class RedirectResponse extends AbstractContainerAware implements
    ResponseInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $code;

    /**
     * Default constructor
     *
     * @param string $url
     *   Path or resource where to redirect
     * @param int $code
     *   HTTP response code
     */
    public function __construct($url, $code = 302)
    {
    }

    public function send($output)
    {
        $url = $this->url;
        if (false === strpos($url, '://')) {
            // Got a resource
            // @todo Prefix with scheme, host and basepath
        } // Else this is a full URL

        header(sprintf('HTTP/1.0 %s %s', $this->code, "Moved"), true, $this->code);
        header(sprintf('Location: %s', $url));
    }
}
