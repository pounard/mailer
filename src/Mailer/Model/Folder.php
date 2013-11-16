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
    private $delimiter;

    /**
     * @var Folder[]
     */
    private $children = null;

    /**
     * Default constructor
     *
     * @param string $name
     * @param Folder $parent
     * @param string $delimiter
     */
    public function __construct($name, $delimiter = '.', array $children = null)
    {
        $this->name = $name;
        $this->delimiter = $delimiter;

        if (null !== $children) {
            $this->children = array();
            foreach ($this->children as $child) {
                $this->addChild($child);
            }
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
        if (null === $this->children) {
            throw new LogicError("Children have not been initialized");
        }

        $this->children[] = $child;

        uasort($this->children, function ($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });
    }

    public function toArray()
    {
        return array(
            'name' => $this->name,
            'delimiter' => $this->delimiter,
            'hasChildren' => !empty($this->children),
            'childCount' => count($this->children),
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
