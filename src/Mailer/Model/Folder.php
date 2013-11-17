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
     * Default constructor
     *
     * @param string $name
     * @param string $path
     * @param \DateTime $lastUpdate
     * @param int $messageCount
     * @param int $recentCount
     * @param string $parent
     * @param string $delimiter
     */
    public function __construct(
        $name,
        $path,
        \DateTime $lastUpdate = null,
        $messageCount         = -1,
        $recentCount          = 0,
        $parent               = null,
        $delimiter            = MailReaderInterface::DEFAULT_DELIMITER)
    {
        $this->name = $name;
        $this->path = $path;
        $this->lastUpdate = $lastUpdate;
        $this->messageCount = $messageCount;
        $this->recentCount = $recentCount;
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
            'lastUpdate'   => $this->lastUpdate,
            'messageCount' => $this->messageCount,
            'recentCount'  => $this->recentCount,
            'parent'       => $this->parent,
            'delimiter'    => $this->delimiter,
        );
    }

    public function fromArray(array $array)
    {
        // Sorry this is a readonly object
    }
}
