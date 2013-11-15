<?php

namespace Mailer\Dispatch\Http;

use Mailer\Dispatch\DefaultRequest;
use Mailer\Dispatch\Request;

/**
 * HTTP request implementation
 */
class HttpRequest extends DefaultRequest
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
     * @return HttpRequest
     */
    static public function createFromGlobals()
    {
        $content = null;

        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                $method  = Request::METHOD_GET;
                break;

            case 'POST':
                $method  = Request::METHOD_POST;
                $content = self::fetchBodyContent();
                break;

            case 'PUT':
                $method  = Request::METHOD_PUT;
                $content = self::fetchBodyContent();
                break;

            case 'DELETE':
                $method  = Request::METHOD_DELETE;
                break;

            default:
                throw new \RuntimeException(sprintf("Invalid request method %s", $_SERVER['REQUEST_METHOD']));
        }

        if (empty($_GET['resource'])) {
            $_GET['resource'] = null;
        }

        return new self($_GET['resource'], $content, $_GET, $method);
    }

    public function __construct($path, $content = null, array $options = array(), $method = Request::METHOD_GET)
    {
        parent::__construct($path, $content, $options, $method);

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $this->setInputContentType($_SERVER['CONTENT_TYPE']);
        } else {
            $this->setInputContentType('application/x-www-form-urlencoded');
        }

        if (isset($_SERVER['HTTP_ACCEPT'])) {
          // FIXME
            $this->setOutputContentTypes(array('text/html'));
        } else {
            $this->setOutputContentTypes(array('text/html'));
        }
    }
}
