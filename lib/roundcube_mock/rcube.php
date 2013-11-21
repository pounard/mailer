<?php

/**
 * Minimal emulation for the Roundcube mail main singleton that will only
 * implement methods we need
 */
class rcube
{
    /**
     * @var rcube
     */
    static private $instance;

    /**
     * @return rcube
     */
    static public function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @var rcube_config
     */
    public $config;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->config = new rcube_config();
        $this->config->init(array(
            'default_charset' => mb_internal_encoding(),
        ));
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
