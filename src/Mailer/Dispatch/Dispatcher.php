<?php

namespace Mailer\Dispatch;

use Mailer\Dispatch\Http\Request;
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
        $request    = new Request();

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
            $controller = $this
                ->getRouter()
                ->findController(
                    $request->getResource()
                );

            // At this point, response should derivated from the incomming
            // request

            // Renderer too

        } catch (LogicError $e) { // FIXME
            echo "<pre>", $e->getMessage(), "\n", $e->getTraceAsString();
        } catch (Error $e) {
            echo "<pre>", $e->getMessage(), "\n", $e->getTraceAsString();
        } catch (\Exception $e) {
            echo "<pre>", $e->getMessage(), "\n", $e->getTraceAsString();
        }
    }
}
