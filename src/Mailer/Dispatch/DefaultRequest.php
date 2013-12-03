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
     * @var string
     */
    protected $inputType;

    /**
     * @var array
     */
    protected $outputType = array();

    /**
     * @var string
     */
    protected $variant;

    /**
     * @var string
     */
    protected $charset;

    /**
     * Default constructor
     *
     * @param string $path
     * @param string $content
     * @param array $options
     */
    public function __construct(
        $path,
        $content       = null,
        array $options = array(),
        $method        = Request::METHOD_GET,
        $variant       = null)
    {
        $this->path    = $path;
        $this->content = $content;
        $this->options = $options;
        $this->method  = $method;
        $this->variant = $variant;
    }

    public function getBasePath()
    {
        // @todo DYNAMIC!
        return '/';
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

    public function hasOption($name)
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

    public function setInputContentType($contentType)
    {
        $this->inputType[] = $contentType;
    }

    public function getInputContentType()
    {
        return $this->inputType;
    }

    public function setOutputContentTypes(array $contentTypeList)
    {
        $this->outputType = $contentTypeList;
    }

    public function getOutputContentTypes()
    {
        return $this->outputType;
    }

    public function getPreferredOutputContentType()
    {
        return reset($this->outputType);
    }

    public function createResponse()
    {
        return new DefaultResponse();
    }

    public function getVariant()
    {
        return $this->variant;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getCharset()
    {
        return $this->charset;
    }
}
