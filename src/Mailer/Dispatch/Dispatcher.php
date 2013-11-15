<?php

namespace Mailer\Dispatch;

use Mailer\Controller\ControllerInterface;
use Mailer\Core\AbstractContainerAware;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\Http\HttpRequest;
use Mailer\Dispatch\Router\DefaultRouter;
use Mailer\Dispatch\Router\RouterInterface;
use Mailer\Error\Error;
use Mailer\Error\LogicError;

class Dispatcher extends AbstractContainerAware
{
    /**
     * Dispatch from the current environement
     */
    static public function run()
    {
        $dispatcher = new self();
        $request    = HttpRequest::createFromGlobals();

        return $dispatcher->dispatch($request);
    }

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

    protected function executeController(RequestInterface $request, $controller)
    {
        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->getContainer());
        }

        if ($controller instanceof ControllerInterface) {
            return $controller->dispatch($request);
        } else if (is_callable($controller)) {
            return call_user_func($controller, $request);
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
            // @todo Determine converter
            // @todo Determine renderer
            $renderer = new \Mailer\Renderer\HtmlRenderer();
            // @todo Find the appropriate response depending on accept
            // and short-circuit if not supported
            $response = new \Mailer\Dispatch\Http\HttpResponse();

            if ($renderer instanceof ContainerAwareInterface) {
                $renderer->setContainer($this->getContainer());
            }
            if ($response instanceof ContainerAwareInterface) {
                $response->setContainer($this->getContainer());
            }

            try {
                // Just for fun: over-indentation!!!!!!
                $view = $this
                    ->executeController(
                        $request,
                        $this
                            ->getRouter()
                            ->findController(
                                $request
                                    ->getResource()
                            )
                );

                $response->send($renderer->render($view));

            } catch (\Exception $e) {
                // Move this out into a specific renderer
                $renderer = new \Mailer\Renderer\HtmlErrorRenderer();
                $response->send($renderer->render($e));
            }
        } catch (\Exception $e) {
            // Very critical error renderer and response could not be
            // spawned: display the raw stack trace
            echo "<pre>", $e->getMessage(), "\n", $e->getTraceAsString() . "</pre>";
        }
    }
}
