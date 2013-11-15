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
        $parts = explode('/', $resource);
        $path  = array();
        $args  = array();

        if (1 < count($parts)) {
            foreach ($parts as $index => $part) {
                if (is_numeric($part)) {
                    $args = array_slice($parts, max(0, $index - 1));
                } else {
                    $path[] = $part;
                }
            }
        } else {
            $path = $parts;
        }

        if (empty($path)) {
            // We have only numeric identifiers
            throw new BadRequestError("Invalid resource path");
        }

        array_walk($path, function (&$value) {
            $value = ucfirst(strtolower($value));
        });

        $className = '\\Mailer\\Controller\\' . implode('\\', $path) . 'Controller';

        if (!class_exists($className)) {
            throw new BadRequestError("Invalid resource path");
        }

        return new $className();
    }
}
