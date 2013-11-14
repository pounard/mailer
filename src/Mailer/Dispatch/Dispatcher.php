<?php

namespace Mailer\Dispatch;

use Mailer\Dispatch\Http\Request;

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
     * Dispatch incomming request
     *
     * @param RequestInterface $request
     */
    public function dispatch(RequestInterface $request)
    {
    }
}
