<?php

namespace Mailer\Dispatch;

use Mailer\Dispatch\Http\HttpRequest;
use Mailer\Dispatch\Router\DefaultRouter;
use Mailer\Dispatch\Router\RouterInterface;
use Mailer\Error\Error;
use Mailer\Error\LogicError;

class Dispatcher
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
    }

    /**
     * Get router
     *
     * @return RouterInterface
     */
    public function getRouter()
    {
        if (null === $this->router) {
            $this->router = new DefaultRouter();
        }

        return $this->router;
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
            $response = new \Mailer\Dispatch\Http\HttpResponse();

            try {
                $controller = $this
                    ->getRouter()
                    ->findController(
                        $request->getResource()
                    );

                // @todo controller return view
                $view = null;
                // @todo Find the appropriate response depending on accept

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
