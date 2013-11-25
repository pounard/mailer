<?php

namespace Mailer\Mime;

abstract class AbstractPart implements PartInterface
{
    /**
     * @var int
     */
    private $index = PartInterface::INDEX_ROOT;

    /**
     * @var string
     */
    private $type;

    /**
     * @var  string
     */
    private $subtype;

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
    }

    public function getSubtype()
    {
        return $this->subtype;
    }
}
