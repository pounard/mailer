<?php

namespace Mailer\Core;

class Message
{
    /**
     * Information/notice
     */
    const TYPE_INFO = 0;

    /**
     * Success
     */
    const TYPE_SUCCESS = 1;

    /**
     * Warning
     */
    const TYPE_WARNING = 2;

    /**
     * Error
     */
    const TYPE_ERROR = 3;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $type;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * Default constructor
     *
     * @param string $message
     * @param int $type
     */
    public function __construct($message, $type = self::TYPE_INFO, \DateTime $date = null)
    {
        $this->message = $message;
        $this->type = $type;
        if (null === $date) {
            $date = new \DateTime();
        }
        $this->date = $date;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
