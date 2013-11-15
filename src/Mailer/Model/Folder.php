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
     * @var Folder
     */
    private $parent;

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
    public function __construct($name, Folder $parent = null, $delimiter = '.', $children = array())
    {
        $this->name = $name;
        $this->parent = $parent;
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
     * Get parent if not top level
     *
     * @return Folder
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Is this folder top level
     *
     * @return boolean
     */
    public function isTopLevel()
    {
        return null === $this->parent;
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
