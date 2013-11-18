<?php

namespace Mailer\Dispatch\Router;

use Mailer\Controller\ControllerInterface;
use Mailer\Controller\IndexController;
use Mailer\Core\AbstractContainerAware;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\NotFoundError;

/**
 * Router interface
 */
class DefaultRouter extends AbstractContainerAware implements RouterInterface
{
    public function findController(RequestInterface $request)
    {
        $resource = $request->getResource();
        $resource = trim($resource);
        $resource = trim($resource, '/\\');

        // Special case: when requested is HTML and no path is given
        // provide the index controller
        if (empty($resource)) {
            $accept = $request->getOutputContentTypes();
            if (in_array("text/html", $accept) || in_array("application/html", $accept)) {
                return array(new IndexController(), array());
            }
        }

        $path = explode('/', $resource);
        $args = array();

        while (!empty($path)) {

            $name = $path;
            array_walk($name, function (&$value) {
                $value = ucfirst(strtolower($value));
            });
            $className = '\\Mailer\\Controller\\' . implode('\\', $name) . 'Controller';

            if (class_exists($className)) {
                return array(new $className(), $args);
            } else {
                array_unshift($args, array_pop($path));
            }
        }

        throw new NotFoundError("Not found");
    }
}
