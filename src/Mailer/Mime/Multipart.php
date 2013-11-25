<?php

namespace Mailer\Mime;

class Multipart extends AbstractPart implements
    \Countable,
    \IteratorAggregate
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
     *
     * @return Multipart
     */
    static public function createInstanceFromArray(array $array)
    {
        $instance = new self();

        if (!empty($array)) {
            if (is_array($array[0])) {

                while (($part = array_shift($array)) && is_array($part)) {
                    if (is_array($part[0])) {
                        // Per RFC3501 multipart can nest multipart
                        $instance->appendPart(self::createInstanceFromArray($part));
                    } else {
                        // RFC3501 nested list of body parts: list stops when
                        // parenthesis ends and we hit a string
                        $instance->appendPart(Part::createInstanceFromArray($part));
                    }
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
                        $instance->setExtensionParameters(Part::parseParameters($part));
                    }
                }

                // Body disposition
                if (!empty($array)) {
                    if ($part = array_shift($array)) { // Can be NIL
                        $disposition = strtolower((string)$part[0]);
                        if (isset($part[1])) {
                            $attributes = Part::parseParameters($part[1]);
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
                $instance->setMultipart(false);
                $instance->appendPart(Part::createInstanceFromArray($array));
            }
        }

        return $instance;
    }

    /**
     * @var boolean
     */
    protected $multipart = true;

    /**
     * @var AbstractPart[]
     */
    protected $parts = array();

    /**
     * @var array
     */
    protected $extensionParameters = array();

    /**
     * @var string
     */
    protected $bodyDisposition = null;

    /**
     * @var array
     */
    protected $bodyDispositionAttributes = array();

    /**
     * Get type
     *
     * Pass-throught to PassInterface::getType() method
     *
     * @return string
     */
    public function getType()
    {
        return 'multipart';
    }

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
    public function appendPart(AbstractPart $part)
    {
        if ($this->multipart) {
            $localIndex = count($this->parts);
            $thisIndex = $this->getIndex();
            if (Part::INDEX_ROOT === $thisIndex) {
                $index = $localIndex;
            } else {
                $index = $thisIndex . AbstractPart::INDEX_SEPARATOR . $localIndex;
            }
            $part->setIndex($index);
        }
        $this->parts[] = $part;
    }

    public function setIndex($index)
    {
        // Overrided because during creation children will be appened
        // here before this very own instance goes into its parent
        if (!empty($this->parts)) {
            foreach ($this->parts as $key => $part) {
                if (Part::INDEX_ROOT === $index) {
                    $part->setIndex($key);
                } else {
                    $part->setIndex($index . AbstractPart::INDEX_SEPARATOR . $key);
                }
            }
        }
    }

    /**
     * Find first part matching the given conditions
     *
     * @param string $type
     * @param string $subtype
     *
     * @return Part
     */
    public function findPartFirst($type = null, $subtype = null)
    {
        foreach ($this->parts as $part) {
            if ($part instanceof Multipart) {
                if ($ret = $part->findPartFirst()) {
                    return $ret;
                }
            } else {
                if ((null === $type || $type === $part->type) && (null === $subtype || $subtype === $part->subtype)) {
                    return $part;
                }
            }
        }
    }

    /**
     * Find all the parts matching the given conditions
     *
     * @param string $type
     * @param string $subtype
     *
     * @return Part
     */
    public function findPartAll($type = null, $subtype = null)
    {
        $ret = array();

        foreach ($this->parts as $part) {
            if ($part instanceof Multipart) {
                foreach ($part->findPartFirst() as $index => $part) {
                    $ret[$index] = $part;
                }
            } else {
                if ((null === $type || $type === $part->type) && (null === $subtype || $subtype === $part->subtype)) {
                    $ret[$part->index] = $part;
                }
            }
        }

        return $ret;
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
