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
     * GET
     */
    const METHOD_GET = 0;

    /**
     * POST
     */
    const METHOD_POST = 1;

    /**
     * PUT
     */
    const METHOD_PUT = 2;

    /**
     * DELETE
     */
    const METHOD_DELETE = 3;

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
    public function hasOptions($name);

    /**
     * Get options value
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null);
}
