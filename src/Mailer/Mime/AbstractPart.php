<?php

namespace Mailer\Mime;

abstract class AbstractPart
{
    /**
     * @var int
     */
    protected $index = Part::INDEX_ROOT;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $subtype;

    /**
     * Set index in parent multipart
     *
     * @param int $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * Get index in parent multipart
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set substype
     *
     * @param string $subtype
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
    }

    /**
     * Get subtype
     *
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Get mimetype built from type and subtype concatenation
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->type . '/' . $this->subtype;
    }

    /**
     * Build headers
     *
     * @param string $setMimeVersion
     */
    protected function buildHeaders($withBoundary = false, $encoding = null, $charset = null)
    {
        $ret = array();

        // Content-Type: text/plain; charset="ISO-8859-1"
        $ret["Content-Type"] = $this->getMimeType();
        if (null !== $charset) {
            // Most mailers seems to send a lowercased value for encoding
            $ret["Content-Type"] .= "; charset=" . strtolower($charset);
        }
        // Content-Type: multipart/alternative; boundary="----=_NextPart_002_001C_00003477.529DE69E"
        if ($withBoundary) {
            $ret["Content-Type"] .= "; boundary=\"" . $this->getBoundary() . "\"";
        }

        // Content-Transfer-Encoding: quoted-printable
        if (null !== $encoding) {
            $ret["Content-Transer-Encoding"] = $encoding;
        }

        return $ret;
    }

    /**
     * Write a single line into string
     *
     * @param resource $resource
     * @param string $string
     * @param string $lf
     */
    protected function writeLineToStream($resource, $string, $lf = Part::DEFAULT_LINE_ENDING)
    {
        if (false === fwrite($resource, $string . $lf)) {
            throw new \RuntimeException("Write error");
        }
    }

    /**
     * Write arbitrary data to stream
     *
     * @param resource $resource
     * @param string $string
     */
    protected function writeToStream($resource, $string)
    {
        if (false === fwrite($resource, $string)) {
            throw new \RuntimeException("Write error");
        }
    }

    /**
     * Generate MIME content and save into given file or resource
     *
     * @param resource|string $output
     * @param boolean $close
     *   If set to false do not close the stream after write
     * @param string $lf
     * @param boolean $setMimeVersion
     *
     * @return boolean
     *   In case of any error this method will raise exceptions at
     *   the only exception of closing file hanlder error: if false
     *   is returned here then the fclose() failed; There is great
     *   chances it has been written anyway and you probably can get
     *   its contents anyway
     */
    abstract public function writeEncodedMime($output, $close = true, $lf = Part::DEFAULT_LINE_ENDING, $setMimeVersion = true);
}
