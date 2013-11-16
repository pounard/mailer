<?php

namespace Mailer\Model;

use Mailer\Error\LogicError;

class Folder implements ExchangeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * Direct parent key
     *
     * @var string
     */
    private $parent;

    /**
     * Default constructor
     *
     * @param string $name
     * @param string $parent
     * @param string $delimiter
     */
    public function __construct($name, $path, $parent, $delimiter = '.')
    {
        $this->name = $name;
        $this->path = $path;
        $this->parent = $parent;
        $this->delimiter = $delimiter;
    }

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
        return array(
            'name' => $this->name,
            'path' => $this->path,
            'parent' => $this->parent,
            'delimiter' => $this->delimiter,
        );
    }

    public function fromArray(array $array)
    {
        // Only name can be edited by the client side
        if (isset($array['name'])) {
            $this->name = $array['name'];
        }
    }
}
