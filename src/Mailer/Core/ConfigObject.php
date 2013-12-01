<?php

namespace Mailer\Core;

use Doctrine\Common\Cache\Cache;

/**
 * This is meant to be API compatible with php-config until I fixes it.
 */
class ConfigObject implements \ArrayAccess
{
    /**
     * Default namespace separator
     */
    const SEP = '/';

    /**
     * Get path from string
     *
     * @param string $string
     *
     * @return string[]
     */
    static private function getPathFromString($string)
    {
        return explode(self::SEP, $string);
    }

    /**
     * Find value in array
     */
    static private function findValue($string, $array)
    {
        $path = explode(self::SEP, $string);
        $cur  = $array;
        $len  = count($path);
        $max  = $len - 1;

        for ($i = 0; $i < $len; ++$i) {
            if (!isset($cur[$path[$i]])) {
                return;
            }
            $cur = $cur[$path[$i]];
            if ($i < $max && !is_array($cur)) {
                return;
            }
        }

        return $cur;
    }

    /**
     * Set value in array
     */
    static private function setValue($value, $string, &$array)
    {
        $path = explode(self::SEP, $string);
        $cur  = &$array;
        $len  = count($path);
        $max  = $len - 1;

        for ($i = 0; $i < $len; ++$i) {
            if ($max === $i) {
                if (null === $value) {
                    unset($cur[$path[$i]]);
                    return;
                } else {
                    $cur[$path[$i]] = $value;
                    return;
                }
            } else if (!isset($cur[$path[$i]])) {
                $cur[$path[$i]] = array();
            }
            $cur = &$cur[$path[$i]];
        }
    }

    /**
     * Recursively merge two arrays
     *
     * @param array $a1
     * @param array $a2
     *
     * @return array
     */
    static private function arrayMergeRecursive(array $a1, array $a2)
    {
        $r = array();
        foreach ($a1 as $k => $v) {
            $r[$k] = $v;
        }
        foreach ($a2 as $k => $v) {
            if (!array_key_exists($k, $r)) {
                $r[$k] = $v;
            } else if (is_array($r[$k]) && is_array($v)) {
                $r[$k] = self::arrayMergeRecursive($r[$k], $v);
            } // Else drop.
        }
        return $r;
    }

    /**
     * User configuration
     */
    private $user = array();

    /**
     * Global configuration
     */
    private $global;

    /**
     * @var scalar
     */
    private $accountId;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * Default constructor
     *
     * @param array $global
     * @param Cache $storage
     */
    public function __construct(
        array $global = array(),
        Cache $cache  = null,
        $accountId    = 0)
    {
        $this->global = $global;

        if ($accountId && $cache) {
            $this->accountId = $accountId;
            $this->cache = $cache;
            if ($ret = $cache->fetch('pref:' . $accountId)) {
                $this->user = $ret;
            }
        }
    }

    public function offsetExists($offset)
    {
        if (null !== self::findValue($offset, $this->user)) {
            return true;
        }
        if (null !== self::findValue($offset, $this->global)) {
            return true;
        }
        return false;
    }

    public function offsetGet($offset)
    {
        if (null !== ($value = self::findValue($offset, $this->user))) {
            return $value;
        }
        if (null !== ($value = self::findValue($offset, $this->global))) {
            return $value;
        }
    }

    public function offsetSet($offset, $value)
    {
        self::setValue($value, $offset, $this->user);

        if ($this->accountId && $this->cache) {
            $this->cache->save('pref:' . $this->accountId, $this->user);
        }
    }

    public function offsetUnset($offset)
    {
        self::setValue(null, $offset, $this->user);

        if ($this->accountId && $this->cache) {
            $this->cache->save('pref:' . $this->accountId, $this->user);
        }
    }

    public function toArray()
    {
        return self::arrayMergeRecursive($this->user, $this->global);
    }
}
