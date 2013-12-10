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
     * Default line ending
     */
    const DEFAULT_LINE_ENDING = "\r\n";

    /**
     * Base 64
     */
    const ENCODING_BASE64 = "base64";

    /**
     * Quoted printable
     */
    const ENCODING_QUOTEDPRINTABLE = "quoted-printable";

    /**
     * 7-bits
     */
    const ENCODING_7BIT = "7bit";

    /**
     * 8-bits
     */
    const ENCODING_8BIT = "8bit";

    /**
     * UUEncode
     */
    const ENCODING_UUENCODE = "uuencode";

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
     * If content is a string we need to desambiguate if it is raw
     * contents or an URI; All other cases this won't be used
     *
     * @var boolean
     */
    protected $isUri = false;

    /**
     * Tell if the source data is encoded
     *
     * @var boolean
     */
    protected $isEncoded = false;

    /**
     * Content line feed if known; This useful only when
     * content is already encoded in source
     *
     * @var string
     */
    protected $contentLf = null;

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
     * Tell if the given content is an URI
     *
     * @return boolean
     */
    public function contentIsUri()
    {
        return $this->isUri;
    }

    /**
     * Tell if the given content is encoded
     *
     * @return boolean
     */
    public function contentIsEncoded()
    {
        return $this->isEncoded;
    }

    /**
     * Set contents
     *
     * @param resource|callback|string $contents
     * @param boolean $isEncoded
     *   If the source data is encoded set this to true
     * @param boolean $isUri
     *   If the given content is a string it will be used as an URI; In all
     *   other cases this parameter will be ignored
     * @param string $lf
     *   If you already know by advance the encoded content line feed char(s)
     *   then write it here, it might save us time by not adding an additional
     *   stream filter when writing back the encoded content
     */
    public function setContents($contents, $isEncoded = false, $isUri = false, $lf = null)
    {
        $this->isUri = false;

        if ($this->isEncoded = $isEncoded) {
            $this->contentLf = $lf;
        } else {
            $this->contentLf = null;
        }

        if (null === $contents) {
            $this->contents = null;
        } else if (is_callable($contents)) {
            $this->contents = $contents;
        } else if (is_resource($contents)) {
            $this->contents = $contents;
        } else if (is_string($contents)) {
            $this->isUri = $isUri;
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
     *   as a valid empty string while false means there was an error anywhere
     *   during the process of creating this instance and content cannot be
     *   fetched
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Get content stream
     *
     * The given stream will be in its current state if it cannot be
     * rewinded; Be aware you are slave of the code that set the content.
     *
     * @param boolean $decoded
     *   Set this to false if you need encoded stream
     * @param string $lf
     *   Line endings to use if you asked for encoded contents; If content
     *   is decoded this parameter will be ignored. If set to null no
     *   conversion will be done
     *
     * @return resource
     */
    public function getContentStream($decoded = true, $lf = null)
    {
        // @todo Encode and write content at the same time with
        // a PHP stream filter
        if (!$decoded && null === $this->encoding) {
            // We don't have an encoding we therefore need to check for
            // one; This is supposed to happen only when this part has
            // been pragmatically created and not fetched from an external
            // server (IMAP, SMTP)
        }

        if (null === $this->contents) {
            // Nothing to stream! Return a null stream implementation
            // without any content there
            return fopen("data://text/plain,", "rb");

        } else if (false === $this->contents) {
            throw new \RuntimeException("Content is false, an error happened sooner");

        } else if (is_string($this->contents)) {
            if ($this->isUri) {
                if (false === ($resource = fopen($this->contents, "rb"))) {
                    throw new \RuntimeException(sprintf("Could not open file '%s'", $this->contents));
                }
            } else {
                // Using this hack will ensure that if PHP goes out of
                // memory it will start pushing data into TMP files
                // instead
                $resource = fopen('php://memory','r+');
                fwrite($resource, $this->contents);
                rewind($resource);
            }
        } else if (is_resource($this->contents)) {
            $resource = $this->contents;
            rewind($resource);
        }

        if ($decoded) {
            if ($this->isEncoded) {
                // Stream filter to decode: here we will consider the
                // decoded data being binary and we won't convert any line
                // ending
                $filter = null;

                switch ($this->encoding) {

                    case self::ENCODING_BASE64:
                        $filter = "convert.base64-decode";
                        break;

                    case self::ENCODING_QUOTEDPRINTABLE:
                        $filter = "convert.quoted-printable-decode";
                        break;

                    /*
                    case self::ENCODING_UUENCODE:
                    case "x-uuencode":
                    case "x-uue":
                    case "uue":
                        $filter = "convert.uudecode";
                        break;

                    case "": // 7BIT
                        $filter = "";
                        break;

                    case "": // 8BIT
                        $filter = "";
                        break;

                    case "": // BINARY
                        $filter = "";
                        break;
                     */

                    default:
                        trigger_error(sprintf("Encoding '%s' not supported, doing nothing", $this->encoding), E_USER_WARNING);
                        break;
                }

                if (null !== $filter) {
                    $params = array(
                        'line-length' => 77,
                        'line-break-chars' => null === $lf ? $lf : "\n"
                    );
                    stream_filter_append($resource, $filter, null, $params);
                }
            }
        } else {
            if ($this->isEncoded) {
                if (null !== $lf && $lf !== $this->contentLf) {
                    // @todo
                    // We don't need to encode but we need to take care of
                    // line endings conversion
                }
            } else {
                $filter = null;

                switch ($this->encoding) {

                    case self::ENCODING_BASE64:
                        $filter = "convert.base64-encode";
                        break;

                    case self::ENCODING_QUOTEDPRINTABLE:
                        $filter = "convert.quoted-printable-encode";
                        break;

                    /*
                    case self::ENCODING_UUENCODE:
                    case "x-uuencode":
                    case "x-uue":
                    case "uue":
                        $filter = "convert.uudecode";
                        break;

                    case "": // 7BIT
                        $filter = "";
                        break;

                    case "": // 8BIT
                        $filter = "";
                        break;

                    case "": // BINARY
                        $filter = "";
                        break;
                     */

                    default:
                        trigger_error(sprintf("Encoding '%s' not supported, doing nothing", $this->encoding), E_USER_WARNING);
                        break;
                }

                if (null !== $filter) {
                    $params = array(
                        'line-length' => 77,
                        'line-break-chars' => (null === $lf ? "\n" : $lf),
                    );
                    stream_filter_append($resource, $filter, null, $params);
                }
            }
        }

        return $resource;
    }

    /**
     * Use the given content information and return the real content
     *
     * Using this seems dumb for files; It's up to you if you want to
     * pollute your memory or not.
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
            if ($this->isUri) {
                return file_get_contents($this->contents);
            } else {
                return $this->contents;
            }
        } else {
            return false;
        }
    }

    public function writeEncodedMime($output, $close = true, $lf = Part::DEFAULT_LINE_ENDING, $setMimeVersion = true)
    {
        $opened = false;

        if (!is_resource($output)) {
            if (false === ($output = fopen($output, "w"))) {
                throw new \RuntimeException(sprintf("Could not open '%s' for writing", $output));
            }
            $opened = true;
        }

        try {
            if ($setMimeVersion) {
                $this->writeLineToStream($output, "MIME-Version: 1.0", $lf);
            }

            $headers = $this->buildHeaders(false, $this->encoding, $this->getParameter("charset"));
            foreach ($headers as $name => $value) {
                $this->writeLineToStream($output, $name . ": " . $value, $lf);
            }

            // And yes we also are going to stream string contents because
            // we can mutualize code this way: small contents won't hurt
            // performances and big contents surely will benefit from being
            // treated as a stream
            $this->writeLineToStream($output, "", $lf);
            $input = $this->getContentStream(false, $lf);

            // Plug both streams and send until the input ending
            if (0 !== stream_copy_to_stream($input, $output)) {
                // Add an empty line right at the end: first ends the
                // encoded text; Second add an empty line for fun
                $this->writeLineToStream($output, "", $lf);
                $this->writeLineToStream($output, "", $lf);
            }

            if ($opened && $close) {
                return fclose($output);
            }

            return true;

        } catch (\Exception $e) {
            if ($opened) {
                fclose($output);
            }
            throw $e;
        }
    }
}
