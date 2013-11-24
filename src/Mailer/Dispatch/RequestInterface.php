<?php

namespace Mailer\Dispatch;

/**
 * Represents an incoming request, mapped on REST
 *
 * Request won't necessarily be an HTTP request but the REST protocol
 * seems appropriate for what we are trying to achieve in this application.
 *
 * Frontend will be fully dissociated from the business and can be in the
 * future a CLI version of the program, using the exact same commands than
 * the web interface.
 *
 * Resource reprensents the controller that will be hit, which may contain
 * one or many actions possible.
 *
 * From the HTTP point of view it is possible to have POST and GET parameters
 * altogether case in which we need to be able to dissociate them: options
 * represent the GET parameters and content whatever has been POST'ed or
 * PUT'ed.
 */
interface RequestInterface
{
    /**
     * Get base path (especially usefull when using HTTP)
     *
     * @return string
     */
    public function getBasePath();

    /**
     * Get asked resource or command
     *
     * @return string
     */
    public function getResource();

    /**
     * Get method
     *
     * @return int
     */
    public function getMethod();

    /**
     * Get whatever content has been sent by
     *
     * @return string
     */
    public function getContent();

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Does this options exists
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasOption($name);

    /**
     * Get options value
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null);

    /**
     * Get the input content type whenever possible
     *
     * @return string
     *    Mime type or null
     */
    public function getInputContentType();

    /**
     * Get the output content type whenever possible
     *
     * @return string[]
     *   Mime types or an empty array if none found
     */
    public function getOutputContentTypes();

    /**
     * Create an appropriate response
     *
     * This method can return null case in which the dispatcher will just
     * send a default implementation that sends plain text
     *
     * @return ResponseInterface
     */
    public function createResponse();
}
