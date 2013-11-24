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

        // @todo Content should be parsed depending on request content type 

        return new self($_GET['resource'], $content, $_GET, $method);
    }

    static public function parseAcceptHeader($header)
    {
         // Regex from Symfony 2 HttpFoundation Request object
         // All the credit goes to them for the following
         // algorithm
         $ret = preg_split('/\s*(?:,*("[^"]+"),*|,*(\'[^\']+\'),*|,+)\s*/', $header, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

         if (!empty($ret)) {
             $ret = array_map(function ($value) {
                if (false !== strpos($value, ';')) {
                    list($type, $q) = explode(';', $value, 2);
                    $q = (float)str_replace('q=', '', $q);
                } else {
                    $type = $value;
                    $q = 1.0;
                }
                return (object)array(
                    'q' => $q,
                    'type' => $type,
                );
             }, $ret);

             // Now order it.
             uasort($ret, function ($a, $b) {
                return ($a->q == $b->q) ? 0 : ($a->q < $b->q ? 1 : -1);
             });

             return array_map(function ($value) {
                return $value->type;
             }, $ret);
         }

         return null;
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
            if ($values = self::parseAcceptHeader($_SERVER['HTTP_ACCEPT'])) {
                $this->setOutputContentTypes($values);
            } else {
                $this->setOutputContentTypes(array('text/html'));
            }
        } else {
            $this->setOutputContentTypes(array('text/html'));
        }
    }

    public function getBasePath()
    {
        // @todo DYNAMIC!
        return '/';
    }

    public function createResponse()
    {
        return new HttpResponse($this);
    }
}
