<?php

namespace Mailer\View\Helper;

use Mailer\View\Helper\Filter\NullFilter;

class FilterFactory
{
    static private $registered = array(
        'autop'   => '\Mailer\View\Helper\Filter\AutoParagraph',
        'htmlesc' => '\Mailer\View\Helper\Filter\HtmlEncode',
        'null'    => '\Mailer\View\Helper\Filter\NullFilter',
        'urltoa'  => '\Mailer\View\Helper\Filter\UrlToLink',
    );

    /**
     * Allow external code to register filter classes
     *
     * @param string $name
     * @param string $class
     */
    static public function register($class, $name = null)
    {
        if (!class_exists($class)) {
            trigger_error(sprintf("Class '%s' does not exist", $class));
        } else {
            if (null === $name) {
                $name = md5($class); // Predictible
            }
            self::$registered[$name] = $class;
        }
    }

    /**
     * @var FilterInterface[]
     */
    private $instances;

    /**
     * Get filter instance
     */
    public function getInstance($name)
    {
        if (!isset($this->instances[$name])) { // Flyweight pattern
            if (!isset(self::$registered[$name])) { // Fallback
                $instance = new NullFilter();
            } else {
                $instance = new self::$registered[$name]();
            }
            $this->instances[$name] = $instance;
        }

        return $this->instances[$name];
    }
}
