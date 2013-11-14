<?php

namespace Mailer\Dispatch\Http;

use Mailer\Dispatch\DefaultRequest;
use Mailer\Dispatch\RequestInterface;

/**
 * HTTP request implementation
 */
class Request extends DefaultRequest
{
    /**
     * Fetch HTTP request body content
     */
    static public function fetchBodyContent()
    {
        return @file_get_contents('php://input');
    }

    /**
     * Get incomming request from PHP globals
     *
     * @return Request
     */
    static public function createFromGlobals()
    {
        $content = null;

        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                $content = self::fetchBodyContent();
                break;

            case 'POST':
                $method  = RequestInterface::METHOD_POST;
                $content = self::fetchBodyContent();
                break;

            case 'PUT':
                $method  = RequestInterface::METHOD_PUT;
                $content = self::fetchBodyContent();
                break;

            case 'DELETE':
                $method  = RequestInterface::METHOD_DELETE;
                break;

            default:
                throw new \RuntimeException(sprintf("Invalid request method %s", $_SERVER['REQUEST_METHOD']));
        }

        if (empty($_GET['resource'])) {
            $_GET['resource'] = null;
        }

        return new Request($_GET['resource'], $content, $_GET, $method);
    }
}
