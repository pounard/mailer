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
     * @var  string
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
}
