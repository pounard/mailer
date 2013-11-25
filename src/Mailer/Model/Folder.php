<?php

namespace Mailer\Model;

use Mailer\Error\LogicError;

class Folder extends AbstractContainer implements ExchangeInterface
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $delimiter = '.';

    /**
     * Direct parent key
     *
     * @var string
     */
    private $parent = null;

    /**
     * Get folder name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get folder path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get last update
     *
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Get message count
     *
     * @return int
     */
    public function getMessageCount()
    {
        return $this->messageCount;
    }

    /**
     * Get recent count
     *
     * @return int
     */
    public function getRecentCount()
    {
       return $this->recentCount;
    }

    /**
     * Get delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Get parent key
     *
     * @return string
     */
    public function getParentKey()
    {
        return $this->parent;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'name'         => $this->name,
            'path'         => $this->path,
            'delimiter'    => $this->delimiter,
            'parent'       => $this->parent,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->name      = $array['name'];
        $this->path      = $array['path'];
        $this->delimiter = $array['delimiter'];
        $this->parent    = $array['parent'];

        if ((null === $this->name || null === $this->parent)) {
            if ((null !== $this->delimiter) && (false !== ($pos = strrpos($this->path, $this->delimiter)))) {
                $this->parent = substr($this->path, 0, $pos);
                $this->name = substr($this->path, $pos + 1);
            } else {
                $this->parent = null;
                $this->name = $this->path;
            }
        }
    }
}
