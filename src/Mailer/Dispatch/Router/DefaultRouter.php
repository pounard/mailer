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

        $parts = explode('/', $resource);
        $path  = array();
        $args  = array();
        $done  = false;

        if (1 < count($parts)) {
            foreach ($parts as $index => $part) {
                if ($done || is_numeric($part)) {
                    $done = true;
                    $args[] = $part;
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

        return array(new $className(), $args);
    }
}
