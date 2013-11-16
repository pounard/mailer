<?php

namespace Mailer\Dispatch;

use Mailer\Controller\ControllerInterface;
use Mailer\Core\AbstractContainerAware;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\Router\DefaultRouter;
use Mailer\Dispatch\Router\RouterInterface;
use Mailer\Error\LogicError;
use Mailer\Model\ArrayConverter;

/**
 * Front dispatcher (application runner)
 */
class Dispatcher extends AbstractContainerAware
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Set router
     *
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;

        if ($this->router instanceof ContainerAwareInterface) {
            $this->router->setContainer($this->getContainer());
        }
    }

    /**
     * Get router
     *
     * @return RouterInterface
     */
    public function getRouter()
    {
        if (null === $this->router) {
            $this->setRouter(new DefaultRouter());
        }

        return $this->router;
    }

    protected function executeController(RequestInterface $request, $controller, $args)
    {
        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->getContainer());
        }

        if ($controller instanceof ControllerInterface) {
            return $controller->dispatch($request, $args);
        } else if (is_callable($controller)) {
            return call_user_func($controller, $request, $args);
        } else {
            throw new LogicError("Controller is broken");
        }
    }

    /**
     * Dispatch incomming request
     *
     * @param RequestInterface $request
     */
    public function dispatch(RequestInterface $request)
    {
        try {
            // Response highly depend on request so let the request
            // a chance to give the appropriate response implementation
            $response = $request->createResponse();
            if (null === $response) {
                $response = new DefaultResponse();
            }

            // @todo Find the appropriate renderer depending on accept
            $renderer = new \Mailer\Renderer\JsonRenderer();

            if ($renderer instanceof ContainerAwareInterface) {
                $renderer->setContainer($this->getContainer());
            }
            if ($response instanceof ContainerAwareInterface) {
                $response->setContainer($this->getContainer());
            }

            list($controller, $args) = $this
                ->getRouter()
                ->findController(
                    $request
                        ->getResource()
                );

            try {
                $result = $this->executeController($request, $controller, $args);

                if ($renderer->needsSerialize()) {
                    $converter = new ArrayConverter();
                    $result = $converter->serialize($result);
                }

                $response->send($renderer->render($result));

            } catch (\Exception $e) {
                $renderer = new \Mailer\Renderer\HtmlErrorRenderer();
                $response->send($renderer->render($e));
            }
        } catch (\Exception $e) {
            $response = new DefaultResponse();
            $response->send($e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
