<?php

namespace Mailer\Model;

use Mailer\Error\LogicError;
use Mailer\Server\MailReaderInterface;

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
     * @var \DateTime
     */
    private $lastUpdate;

    /**
     * @var int
     */
    private $messageCount;

    /**
     * @var int
     */
    private $recentCount;

    /**
     * @var int
     */
    private $unseenCount;

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
        return array(
            'name'         => $this->name,
            'path'         => $this->path,
            'delimiter'    => $this->delimiter,
            'parent'       => $this->parent,
            'lastUpdate'   => $this->lastUpdate,
            'messageCount' => $this->messageCount,
            'recentCount'  => $this->recentCount,
            'unseenCount'  => $this->unseenCount,
        );
    }

    public function fromArray(array $array)
    {
        $array += array(
            'name'         => null,
            'path'         => null,
            'delimiter'    => null,
            'parent'       => null,
            'lastUpdate'   => null,
            'messageCount' => 0,
            'recentCount'  => 0,
            'unseenCount'  => 0,
        );

        $this->name         = $array['name'];
        $this->path         = $array['path'];
        $this->delimiter    = $array['delimiter'];
        $this->parent       = $array['parent'];
        $this->lastUpdate   = $array['lastUpdate'];
        $this->messageCount = (int)$array['messageCount'];
        $this->recentCount  = (int)$array['recentCount'];
        $this->unseenCount  = (int)$array['unseenCount'];
    }
}
