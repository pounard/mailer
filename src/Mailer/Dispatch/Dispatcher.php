<?php

namespace Mailer\Dispatch;

use Mailer\Controller\ControllerInterface;
use Mailer\Core\AbstractContainerAware;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\Router\DefaultRouter;
use Mailer\Dispatch\Router\RouterInterface;
use Mailer\Error\LogicError;
use Mailer\Model\ArrayConverter;
use Mailer\View\View;

/**
 * Front dispatcher (application runner)
 */
class Dispatcher extends AbstractContainerAware
{
    /**
     * Not ideal but working map of mime types and class to use
     */
    static $responseMap = array(
        'text/html' => '\\Mailer\\View\\HtmlRenderer',
        'application/xhtml+xml' => '\\Mailer\\View\\HtmlRenderer',
        'application/json' => '\\Mailer\\View\\JsonRenderer',
        'text/javascript' => '\\Mailer\\View\\JsonRenderer',
    );

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

    /**
     * Execute controller and fetch a view
     *
     * @param RequestInterface $request
     * @param callable|ControllerInterface $controller
     * @param array $args
     *
     * @return View
     */
    protected function executeController(
        RequestInterface $request,
        $controller,
        array $args)
    {
        $view = null;

        // Controller can be a response if router told us so
        if ($controller instanceof ResponseInterface) {
            return $controller;
        }

        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->getContainer());
        }

        if ($controller instanceof ControllerInterface) {
            $view = $controller->dispatch($request, $args);
        } else if (is_callable($controller)) {
            $view = call_user_func($controller, $request, $args);
        } else {
            throw new LogicError("Controller is broken");
        }

        // Allows controller to return the response directly
        // and bypass the native rendering pipeline
        if ($view instanceof ResponseInterface && !$view instanceof View) {
            $view = new View($view);
        }
        if ($view instanceof ContainerAwareInterface) {
            $view->setContainer($this->getContainer());
        }

        return $view;
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

            // Attempt to determine the renderer depending on the incomming
            // request. I'm not proud of this algorithm but it works quite
            // well: ideally I'll move it out
            $renderer = null;
            foreach ($request->getOutputContentTypes() as $type) {
                if (isset(self::$responseMap[$type])) {
                    $renderer = new self::$responseMap[$type]();
                    continue;
                }
            }
            if (null === $renderer) {
                $renderer = new \Mailer\View\HtmlRenderer();
            }
            //$renderer = new \Mailer\View\JsonRenderer();

            if ($renderer instanceof ContainerAwareInterface) {
                $renderer->setContainer($this->getContainer());
            }
            if ($response instanceof ContainerAwareInterface) {
                $response->setContainer($this->getContainer());
            }

            try {
                list($controller, $args) = $this
                    ->getRouter()
                    ->findController($request);

                $view = $this->executeController($request, $controller, $args);

                if ($view instanceof ResponseInterface) {
                    $view->send(null);
                } else {
                    // Because one liners are too mainstream
                    $response->send($renderer->render($view));
                }

            // Within exception handling the dispatcher will act as a controller
            } catch (\Exception $e) {
                $response->send($renderer->render(new View(array('e' => $e), 'error')));
            }
        } catch (\Exception $e) {
            $response = new DefaultResponse();
            $response->send($e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
