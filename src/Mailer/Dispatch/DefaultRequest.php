<?php

namespace Mailer\Dispatch;

class DefaultRequest implements RequestInterface
{
    /**
     * @var int
     */
    protected $method;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $options;

    /**
     * Default constructor
     *
     * @param string $path
     * @param string $content
     * @param array $options
     */
    public function __construct($path, $content = null, array $options = array(), $method = RequestInterface::METHOD_GET)
    {
        $this->path    = $path;
        $this->content = $content;
        $this->options = $options;
    }

    public function getResource()
    {
        return $this->path;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function hasOptions($name)
    {
        return array_key_exists($name, $this->options);
    }

    public function getOption($name, $default = null)
    {
        if ($this->hasOption($name)) {
            return $this->options[$name];
        } else {
            return $default;
        }
    }
}
