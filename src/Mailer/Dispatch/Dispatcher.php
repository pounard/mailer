<?php

namespace Mailer\Dispatch;

use Mailer\Controller\ControllerInterface;
use Mailer\Core\AbstractContainerAware;
use Mailer\Core\ContainerAwareInterface;
use Mailer\Dispatch\Http\HttpResponse;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\Router\DefaultRouter;
use Mailer\Dispatch\Router\RouterInterface;
use Mailer\Error\LogicError;
use Mailer\Error\UnauthorizedError;
use Mailer\Model\ArrayConverter;
use Mailer\View\HtmlRenderer;
use Mailer\View\NullRenderer;
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
        if (!$view instanceof ResponseInterface && !$view instanceof View) {
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
                    break;
                }
            }
            if (null === $renderer) {
                $renderer = new \Mailer\View\HtmlRenderer();
            }

            if ($renderer instanceof ContainerAwareInterface) {
                $renderer->setContainer($this->getContainer());
            }
            if ($response instanceof ContainerAwareInterface) {
                $response->setContainer($this->getContainer());
            }

            try {
                // Most dispatching magic happens here
                list($controller, $args) = $this->getRouter()->findController($request);
                $view = $this->executeController($request, $controller, $args);
                $contentType = $renderer->getContentType();

                if ($view instanceof ResponseInterface) {
                    $view->send($request, null);
                } else {
                    // Where there is nothing to render just switch to a null
                    // implementation that will put nothing into the payload
                    if (!$response instanceof HttpResponse && $view->isEmpty()) {
                        $renderer = new NullRenderer();
                    }
                    // Because one liners are too mainstream
                    $response->send($request, $renderer->render($view, $request), $contentType);
                }

            // Within exception handling the dispatcher will act as a controller
            } catch (UnauthorizedError $e) {
                // FIXME: This code should not live here
                if ($renderer instanceof HtmlRenderer) {
                    // If HTML is the demanded protocol then redirect to the
                    // login controller whenever the user is not authenticated
                    if ($this->getContainer()->getSession()->isAuthenticated()) {
                        $response->send($request, $renderer->render(new view(array('e' => $e), 'app/unauth')));
                    } else {
                        $response = new RedirectResponse('app/login');
                        $response->send($request, null);
                    }
                } else {
                    // Unauthorized error will end up releasing a 403 error in
                    // the client demanded protocol
                    $response->send($request, $renderer->render(new View(array('e' => $e), 'app/error'), $request));
                }
            } catch (\Exception $e) {
                $response->send($request, $renderer->render(new View(array('e' => $e), 'app/error'), $request));
            }
        } catch (\Exception $e) {
            $response = new DefaultResponse();
            $response->send($request, $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
