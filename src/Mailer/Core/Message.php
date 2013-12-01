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
     * Get a textual representation (machine name) for the type
     *
     * @return string
     */
    public function getTypeString()
    {
        switch ($this->type) {

            case self::TYPE_ERROR:
                return 'error';

            case self::TYPE_WARNING:
                return 'warning';

            case self::TYPE_SUCCESS:
                return 'success';

            case self::TYPE_INFO:
            default:
                return 'info';
        }
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
