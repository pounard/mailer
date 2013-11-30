<?php

namespace Mailer\Controller;

use Mailer\Dispatch\Http\HttpRequest;
use Mailer\Dispatch\Http\HttpResponse;

/**
 * /api will serve as a preflight check
 */
class ApiController extends AbstractController
{
    public function optionsAction($request, $args)
    {
        if ($request instanceof HttpRequest) {
            return new HttpResponse($request, array(
                "Access-Control-Request-Method" => "GET, POST, PATCH, PUT, DELETE, OPTIONS",
                "Access-Control-Allow-Origin"   => "*",
            ));
        }
    }
}
