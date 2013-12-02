<?php

namespace Mailer\Mime;

class Part extends AbstractPart
{
    /**
     * If the parsed content was not valid multipart data, the index will be
     * set to this constant. Use this when trying to fetch content from the
     * IMAP server as index and it will return you the full content string
     * instead of a single part.
     */
    const INDEX_ROOT = '';

    /**
     * Index separator for FETCH queries
     */
    const INDEX_SEPARATOR = '.';

    /**
     * Parse parameters list as an hashmap
     *
     * @param array $list
     *
     * @return array
     */
    static public function parseParameters(array $list)
    {
        $ret = array();

        $key = null;
        foreach ($list as $value) {
            if (null === $key) {
                $key = $value;
            } else {
                $ret[$key] = $value;
                $key = null;
            }
        }

        if (null !== $key) {
            // Malformed options list
            throw new \InvalidArgumentException("Malformed option list item count is odd");
        }

        return $ret;
    }

    /**
     * Create instance from array
     *
     * This method parses data accordingly to standard RFC3501 then leave
     * type specific options to the type specific implementation: this class
     * may be used as a generic implementation but will not really be of any
     * kind of help; It allows a graceful downgrade for non supported protocol
     * extensions
     *
     * Note that protocol string constants such as type or body type will be
     * strtolower()-ed for convenience purpose, allowing the user to do strict
     * string comparison or using switch() statements without prior conversion
     *
     * @param array $array
     *   Raw single body structure data array as described in the README.md file
     *
     * @return Part
     *
     * @see \Mailer\Mime\Part\Message
     * @see \Mailer\Mime\Part\Text
     */
    static public function createInstanceFromArray(array $array)
    {
        $type = null;
        $isKnownType = true;
        if (!empty($array)) {
            $type = strtolower(array_shift($array));
        }
        if (!$type) {
            throw new \InvalidArgumentException("Empty body part given");
        }

        $className = __NAMESPACE__ . '\Part\\' . ucfirst(strtolower(
            // Avoid "\0" null string security breach while including
            stripcslashes($type)
        ));
        if (class_exists($className)) {
            $instance = new $className();
        } else {
            //trigger_error(sprintf("Unsuported body part type '%s': using generic implementation", $type));
            $isKnownType = false;
            $instance = new Part();
        }
        $instance->setType($type);

        if (!empty($array)) {
            if ($part = array_shift($array)) { // Can be NIL
                $instance->setSubtype(strtolower($part));
            }
        }

        if (!empty($array)) {
            if ($part = array_shift($array)) { // Can be NIL
                $instance->setParameters(Part::parseParameters($part));
            }
        }

        if (!empty($array)) {
            if ($part = array_shift($array)) { // Can be NIL
                $instance->setContentId($part);
            }
        }

        if (!empty($array)) {
            if ($part = array_shift($array)) { // Can be NIL
                $instance->setDescription($part);
            }
        }

        if (!empty($array)) {
            if ($part = array_shift($array)) { // Can be NIL
                $instance->setEncoding($part);
            }
        }

        if (!empty($array)) {
            if ($part = array_shift($array)) { // Can be NIL
                $instance->setSize((int)$part);
            }
        }

        if (!empty($array) && $isKnownType) {
            if ($array = $instance->parseAdditionalData($array)) {
                $instance->parseExtensionData($array);
            }
        }

        return $instance;
    }

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $contentId;

    /**
     * @var string
     */
    protected $encoding;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var string
     */
    protected $contents;

    /**
     * Parse additional extension data that this generic class
     * does not know about
     *
     * If you are implementing such message you should know that after this
     * call the parseExtensionData() method will be called; If you leave data
     * you should have consumed in the return array this method will fail: if
     * you cannot parse completely your data either you need to prune extension
     * defined data you dont know how to consume or return false to disable the
     * parseExtensionData() call. Any null of false value will be cached as an
     * error.
     *
     * @param array $array
     *   Data provided is the FETCH-ed raw data with generic body part
     *   parameters removed (handled by this generic implementation)
     *
     * @return array
     *   Data array with consumed data pruned out
     *
     * @see \Mailer\Mime\Part\Text
     *   As a valid and almost complete example
     */
    public function parseAdditionalData(array $array)
    {
        return $array;
    }

    /**
     * Parse basic extension data
     *
     * @param array $data
     *
     * @return array
     *   Data array with consumed data pruned out
     */
    public function parseExtensionData(array $data)
    {
        /*
         * RFC3501
         * @todo
         *
          body MD5
            A string giving the body MD5 value as defined in [MD5].
          body disposition
            A parenthesized list with the same content and function as
            the body disposition for a multipart body part.
          body language
            A string or parenthesized list giving the body language
            value as defined in [LANGUAGE-TAGS].
          body location
            A string list giving the body content URI as defined in
            [LOCATION].
         */

        // FIXME Temporary removes basic fields
        for ($i = 0; $i < 4; ++$i) {
            if (empty($array)) {
                return array();
            } else {
                array_shift($array);
            }
        }
    }

    /**
     * Set parameters
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get single parater
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            return $default;
        }
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set content id
     *
     * @param string $contentId
     */
    public function setContentId($contentId)
    {
        $this->contentId = $contentId;
    }

    /**
     * Get content id
     *
     * @return string
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * Set encoding
     *
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set size
     *
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get size
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set contents
     *
     * @param resource|callback|string $contents
     */
    public function setContents($contents)
    {
        if (null === $contents) {
            $this->contents = null;
        } else if (is_callable($contents)) {
            $this->contents = $contents;
        } else if (is_resource($contents)) {
            $this->contents = $contents;
        } else if (is_string($contents)) {
            $this->contents = $contents;
        } else {
            throw new \InvalidArgumentException("Contents must be either null, callable, string or resource");
        }
    }

    /**
     * Get contents
     *
     * @return resource|callback|string
     *   Content value, empty string is a valid value, null must be treated
     *   as a valid empty string while false means there was an error while
     *   fetching content
     */
    public function getContents()
    {
        // This could be skipped and returned implictely but it makes
        // it more readable this way: strict false means there was an
        // error while fetching the content, which any other case such
        // as an empty string is a valid content
        if (false === $this->contents) {
            return false;
        }
        return $this->contents;
    }

    /**
     * Use the given content information and return the real content
     *
     * @return string
     *   If false then an error happened, if null there is no content
     */
    public function getContentsReal()
    {
        if (false === $this->contents) {
            return false;
        } else if (null === $this->contents) {
            return null;
        } else if (is_callable($this->contents)) {
            return call_user_func($this->contents);
        } else if (is_resource($this->contents)) {
            return stream_get_contents($this->contents);
        } else if (is_string($this->contents)) {
            return $contents;
        } else {
            return false;
       }
    }
}
