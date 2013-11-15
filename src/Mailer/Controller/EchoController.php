<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;

/**
 * Return parameters from the request
 */
class EchoController extends AbstractController
{
    public function deleteAction(RequestInterface $request)
    {
        return array(
            'resource' => $request->getResource(),
            'method'   => Request::methodToString($request->getMethod()),
            'options'  => $request->getOptions(),
            'content'  => $request->getContent(),
        );
    }

    public function getAction(RequestInterface $request)
    {
        return array(
            'resource' => $request->getResource(),
            'method'   => Request::methodToString($request->getMethod()),
            'options'  => $request->getOptions(),
        );
    }

    public function postAction(RequestInterface $request)
    {
        return array(
            'resource' => $request->getResource(),
            'method'   => Request::methodToString($request->getMethod()),
            'options'  => $request->getOptions(),
            'content'  => $request->getContent(),
        );
    }

    public function putAction(RequestInterface $request)
    {
        return array(
            'resource' => $request->getResource(),
            'method'   => Request::methodToString($request->getMethod()),
            'options'  => $request->getOptions(),
            'content'  => $request->getContent(),
        );
    }
}
