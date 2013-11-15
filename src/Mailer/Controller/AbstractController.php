<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\MethodNotAllowedError;

abstract class AbstractController implements ControllerInterface
{
    public function dispatch(RequestInterface $request)
    {
        $method = null;

        switch ($request->getMethod()) {

            case Request::METHOD_DELETE:
                return $this->deleteAction($request);

            case Request::METHOD_GET:
                return $this->getAction($request);

            case Request::METHOD_POST:
                return $this->postAction($request);

            case Request::METHOD_PUT:
                return $this->putAction($request);

            default:
                throw new MethodNotAllowedError();
        }
    }

    public function deleteAction(RequestInterface $request)
    {
        throw new MethodNotAllowedError();
    }

    public function getAction(RequestInterface $request)
    {
        throw new MethodNotAllowedError();
    }

    public function postAction(RequestInterface $request)
    {
        throw new MethodNotAllowedError();
    }

    public function putAction(RequestInterface $request)
    {
        throw new MethodNotAllowedError();
    }
}
