<?php

namespace Mailer\View\Helper;

use Mailer\Core\AbstractContainerAware;
use Mailer\View\Helper\Filter\NullFilter;

class FilterFactory extends AbstractContainerAware
{
    static private $registered = array(
        'autop'   => '\Mailer\View\Helper\Filter\AutoParagraph',
        'htmlesc' => '\Mailer\View\Helper\Filter\HtmlEncode',
        'lntohr'  => '\Mailer\View\Helper\Filter\StupidLinesToHr',
        'lntovd'  => '\Mailer\View\Helper\Filter\StupidLinesToVoid',
        'null'    => '\Mailer\View\Helper\Filter\NullFilter',
        'strip'   => '\Mailer\View\Helper\Filter\Strip',
        'urltoa'  => '\Mailer\View\Helper\Filter\UrlToLink',
        'urltou'  => '\Mailer\View\Helper\Filter\UrlToUrl',
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
     * @var FitlerInterface[]
     */
    private $filters = array();

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

    /**
     * Get a filter collection using the given filter types
     *
     * @param array $types
     *   Ordered array of filter types
     *
     * @return FilterCollection
     */
    private function getCollectionFrom(array $types)
    {
        foreach ($types as $index => $type) {
            $types[$index] = $this->getInstance($type);
        }

        return new FilterCollection($types);
    }

    /**
     * Get filter for the given text type
     *
     * @param string $type
     *
     * @return FilterInterface
     */
    public function getFilter($type)
    {
        if (isset($this->filters[$type])) {
            return $this->filters[$type];
        }

        // Fetch type configuration
        $config = $this->getContainer()->getConfig();
        if (isset($config['filters'][$type])) {
            $types = $config['filters'][$type];
        } else {
            $types = array('strip'); // Default must be secure
        }

        return $this->filters[$type] = $this->getCollectionFrom($types);
    }
}
