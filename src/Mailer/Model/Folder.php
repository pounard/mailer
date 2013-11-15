<?php

namespace Mailer\Model;

class Folder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var Folder[]
     */
    private $children = array();

    /**
     * Default constructor
     *
     * @param string $name
     * @param Folder $parent
     * @param string $delimiter
     */
    public function __construct($name, $delimiter = '.', $children = array())
    {
        $this->name = $name;
        $this->delimiter = $delimiter;

        foreach ($this->children as $child) {
            $this->addChild($child);
        }
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
     * Get delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Get children
     *
     * @return Folder[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add child
     *
     * @param Folder $child
     */
    public function addChild(Folder $child)
    {
        $this->children[] = $child;

        uasort($this->children, function ($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });
    }
}
