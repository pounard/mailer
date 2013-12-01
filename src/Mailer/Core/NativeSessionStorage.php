<?php

namespace Mailer\Core;

class NativeSessionStorage implements \ArrayAccess
{
    /**
     * Default namespace
     */
    const NS_DEFAULT = 'mailer';

    /**
     * @var string
     */
    protected $namespace = self::NS_DEFAULT;

    public function offsetExists($offset)
    {
        return isset($_SESSION[$this->namespace][$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($_SESSION[$this->namespace][$offset])) {
            return $_SESSION[$this->namespace][$offset];
        }
    }

    public function offsetSet($offset, $value)
    {
        $_SESSION[$this->namespace][$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($_SESSION[$this->namespace][$offset]);
    }
}
