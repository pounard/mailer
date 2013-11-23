<?php

namespace Mailer\Server\Protocol\Body;

use Mailer\Server\ProtocolHelper;

class Multipart implements \Countable, \IteratorAggregate
{
    /**
     * Create instance from array
     *
     * This method deals with RFC3501 defined extensions but does not
     * provide support for any additional extension. Additional non
     * supported extensions raw data will be included into this object
     * for implementor to access it.
     *
     * Note that protocol string constants such as type or body type will be
     * strtolower()'d for convenience purpose, allowing the user to do strict
     * string comparison or using switch() statements without prior conversion
     *
     * @param array $array
     *   Raw body structure data array as described in the README.md file
     * @param callback $fetchCallback
     *   Single part fetch callback, server and backend dependent,
     *   implementation
     *
     * @return Multipart
     *
     * @see Part::fetchCallback
     * @see Part::createInstanceFromArray()
     * @see Part::setContents()
     */
    static public function createInstanceFromArray(array $array, $fetchCallback = null)
    {
        $instance = new self();

        if (!empty($array)) {
            if (is_array($array[0])) {

                // RFC3501 nested list of body parts: list stops when
                // parenthesis ends and we hit a string
                while (($part = array_shift($array)) && is_array($part)) {
                    $instance->appendPart(Part::createInstanceFromArray($part, $fetchCallback));
                }

                // RFC3501 first string is the multipart subtype
                // i.e. MIXED, DIGEST, PARALLEL, ALTERNATIVE, ...
                // @todo But it could be not present (is that valid?)
                if (isset($part)) {
                    $instance->setSubtype(strtolower((string)$part));
                }

                // RFC3501 Extension data follows IF PRESENT

                // Body parameters
                if (!empty($array)) {
                    // Roundcube implementation returns some NULL values
                    if ($part = array_shift($array)) { // Can be NIL
                        $instance->setExtensionParameters(ProtocolHelper::parseParameters($part));
                    }
                }

                // Body disposition
                if (!empty($array)) {
                    if ($part = array_shift($array)) { // Can be NIL
                        $disposition = strtolower((string)$part[0]);
                        if (isset($part[1])) {
                            $attributes = ProtocolHelper::parseParameters($part[1]);
                        } else {
                            $attributes = array();
                        }
                        $instance->setBodyDisposition($disposition, $attributes);
                    }
                }

                // Body language
                if (!empty($array)) {
                    if ($part = array_shift($array)) { // Can be NIL
                        // @todo
                    }
                }

                // Body location
                if (!empty($array)) {
                    if ($part = array_shift($array)) { // Can be NIL
                        // @todo
                    }
                }

                // RFC3501 states that follows extension data that is not
                // described in the RFC itself: the client MUST accept this
                // data and use it, if the server handles those extensions
                // then it's present as STRING, STRING PARENTHESIS LIST,
                // STRING PARAMETERS LIST, NESTED PARENTHESIS, etc...
                // @todo

            } else {
                // RFC3501 There can be only one part then it's not multipart
                // This code abstract it to multipart anyway
                $instance->appendPart(Part::createInstanceFromArray($array, $fetchCallback));
                $instance->setMultipart(false);
            }
        }

        return $instance;
    }

    /**
     * @var boolean
     */
    private $multipart = true;

    /**
     * @var string
     */
    private $subtype;

    /**
     * @var Part[]
     */
    private $parts = array();

    /**
     * @var array
     */
    private $extensionParameters = array();

    /**
     * @var string
     */
    private $bodyDisposition = null;

    /**
     * @var array
     */
    private $bodyDispositionAttributes = array();

    /**
     * Change multipart flag
     *
     * @param boolean $multipart
     */
    public function setMultipart($multipart)
    {
        $this->multipart = $multipart;
    }

    /**
     * Is this really multipart
     *
     * @return boolean
     */
    public function isMultipart()
    {
        return $this->multipart;
    }

    /**
     * Get multipart subtype
     *
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Set multipart subtype
     *
     * @param string $subtype
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
    }

    /**
     * Set extension parameters
     *
     * @param array $parameters
     */
    public function setExtensionParameters(array $parameters)
    {
        $this->extensionParameters = $parameters;
    }

    /**
     * Get extension parameters
     *
     * @return array
     */
    public function getExtensionParameters()
    {
        return $this->extensionParameters;
    }

    /**
     * Set body disposition
     *
     * @param string $disposition
     * @param array $attributes
     */
    public function setBodyDisposition($disposition, array $attributes = array())
    {
        $this->bodyDisposition = $disposition;
        $this->bodyDispositionAttributes = $attributes;
    }

    /**
     * Get body disposition
     *
     * @return string
     */
    public function getBodyDisposition()
    {
        return $this->bodyDisposition;
    }

    /**
     * Get body disposition attributes
     *
     * @return array
     */
    public function getBodyDispositionAttributes()
    {
        return $this->bodyDispositionAttributes;
    }

    /**
     * Get all parts
     *
     * @return Part[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Get part at index
     *
     * @param int $index
     *
     * @return Part
     */
    public function getPartAt($index = 0)
    {
        if (!isset($this->parts[$index])) {
            throw new \OutOfBoundsException("No part at given index");
        }

        return $this->parts[$index];
    }

    /**
     * Append part
     *
     * @param Part $part
     */
    public function appendPart(Part $part)
    {
        $part->setIndex(count($this->parts)); // Index starts by 0
        $this->parts[] = $part;
    }

    public function count()
    {
        return count($this->parts);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->parts);
    }
}