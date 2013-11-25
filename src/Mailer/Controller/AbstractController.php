<?php

namespace Mailer\Controller;

use Mailer\Core\AbstractContainerAware;
use Mailer\Dispatch\Request;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\MethodNotAllowedError;
use Mailer\Error\UnauthorizedError;
use Mailer\Server\Imap\Query;

abstract class AbstractController extends AbstractContainerAware implements
    ControllerInterface
{
    /**
     * Get query from request
     *
     * @param RequestInterface $request
     *
     * @return Query
     */
    public function getQueryFromRequest(RequestInterface $request)
    {
        return new Query(
            $request->getOption('limit',  Query::LIMIT_DEFAULT),
            $request->getOption('offset', Query::OFFSET_DEFAULT),
            $request->getOption('sort',   Query::SORT_SEQ),
            $request->getOption('order',  Query::ORDER_DESC)
        );
    }

    public function dispatch(RequestInterface $request, array $args)
    {
        if (!$this->isAuthorized($request, $args)) {
            throw new UnauthorizedError();
        }

        switch ($request->getMethod()) {

            case Request::METHOD_DELETE:
                return $this->deleteAction($request, $args);

            case Request::METHOD_GET:
                return $this->getAction($request, $args);

            case Request::METHOD_POST:
                return $this->postAction($request, $args);

            case Request::METHOD_PUT:
                return $this->putAction($request, $args);

            default:
                throw new MethodNotAllowedError();
        }
    }

    public function isAuthorized(RequestInterface $request, array $args)
    {
        return $this
            ->getContainer()
            ->getSession()
            ->isAuthenticated();
    }

    public function deleteAction(RequestInterface $request, array $args)
    {
        throw new MethodNotAllowedError();
    }

    public function getAction(RequestInterface $request, array $args)
    {
        throw new MethodNotAllowedError();
    }

    public function postAction(RequestInterface $request, array $args)
    {
        throw new MethodNotAllowedError();
    }

    public function putAction(RequestInterface $request, array $args)
    {
        throw new MethodNotAllowedError();
    }
}
