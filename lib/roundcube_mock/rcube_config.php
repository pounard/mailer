<?php

/**
 * Config emulation for Rounbube rcube fake class
 */
class rcube_config
{
    /**
     * @var array
     */
    private $options;

    /**
     * Init the instance with options
     *
     * @param array $options
     */
    public function init(array $options)
    {
        $this->options = $options;
    }

    public function get($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
    }

    public function __get($name)
    {
        trigger_error(sprintf("Trying to get '%s' property on \rcube singleton", $name));
    }

    public function __call($name, array $arguments)
    {
        trigger_error(sprintf("Trying to call '%s' method on \rcube singleton", $name));
    }
}
