<?php

namespace Mailer\Dispatch\Router;

use Mailer\Controller\ControllerInterface;
use Mailer\Error\BadRequestError;

/**
 * Router interface
 */
class DefaultRouter implements RouterInterface
{
    public function findController($resource)
    {
        $resource = trim($resource);
        $resource = trim($resource, '/\\');

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

        throw new BadRequestError("Invalid resource path");
    }
}
