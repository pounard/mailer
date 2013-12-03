<?php

namespace Mailer\Dispatch\Http;

use Mailer\Dispatch\DefaultRequest;
use Mailer\Dispatch\Request;
use Mailer\Error\UnsupportedMediaTypeError;

/**
 * HTTP request implementation
 */
class HttpRequest extends DefaultRequest
{
    /**
     * Fetch HTTP request body content
     */
    static public function fetchBodyContent($contentType)
    {
        switch ($contentType) {

            case 'application/x-www-form-urlencoded':
                return $_POST;

            default:
                // FIXME: PATCH command with jQuery always sends url encoded
                $content = array();
                parse_str(@file_get_contents('php://input'), $content);
                return $content;

                /*
            default:
                // FIXME Only supports JSON right now
                if (false !== strpos($contentType, 'json')) {
                    $content = @file_get_contents('php://input');
                    if (!empty($content)) {
                        if (!$content = json_decode($content)) {
                            // FIXME Find the right error code for invalid content
                            throw new UnsupportedMediaTypeError();
                        }
                        return $content;
                    }
                }
                throw new UnsupportedMediaTypeError();
                */
        }
    }

    /**
     * Get incomming request from PHP globals
     *
     * @return HttpRequest
     */
    static public function createFromGlobals()
    {
        $content = null;
        $charset = "UTF-8"; // FIXME
        $contentType = null;

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'];
        } else { // Be liberal in what you accept
            // FIXME attempt to determine content automatically
            $contentType = 'application/x-www-form-urlencoded';
        }

        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                $method  = Request::METHOD_GET;
                break;

            case 'POST':
                $method  = Request::METHOD_POST;
                $content = self::fetchBodyContent($contentType);
                break;

            case 'PUT':
                $method  = Request::METHOD_PUT;
                $content = self::fetchBodyContent($contentType);
                break;

            case 'DELETE':
                $method  = Request::METHOD_DELETE;
                break;

            case 'PATCH':
                $method  = Request::METHOD_PATCH;
                $content = self::fetchBodyContent($contentType);
                break;

            case 'OPTIONS':
                $method  = Request::METHOD_OPTIONS;
                break;

            default:
                throw new \RuntimeException(sprintf("Invalid request method %s", $_SERVER['REQUEST_METHOD']));
        }

        $variant = null;
        if (empty($_GET['resource'])) {
            $_GET['resource'] = null;
        } else if (false !== ($pos = strpos($_GET['resource'], ';'))) {
            $variant = substr($_GET['resource'], $pos + 1);
            $_GET['resource'] = substr($_GET['resource'], 0, $pos);
        }

        $request = new self($_GET['resource'], $content, $_GET, $method, $variant);
        $request->setInputContentType($contentType);
        $request->setCharset($charset);

        return $request;
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

    public function __construct(
        $path,
        $content       = null,
        array $options = array(),
        $method        = Request::METHOD_GET,
        $variant       = null)
    {
        parent::__construct($path, $content, $options, $method, $variant);

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
