<?php

namespace Mailer\Core;

use Mailer\Model\AbstractObject;
use Mailer\Model\ExchangeInterface;

class Message extends AbstractObject
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
     * Get type from string
     *
     * @param string $string
     *
     * @return int
     */
    static public function getTypeFromString($string)
    {
          switch ($string) {

            case 'error':
                return self::TYPE_ERROR;

            case 'warning':
                return self::TYPE_WARNING;

            case 'success':
                return self::TYPE_SUCCESS;

            case 'info':
            default:
                return self::TYPE_INFO;
        }
    }

    /**
     * Get string from type
     *
     * @param int $type
     *
     * @return string
     */
    static public function getStringFromType($type)
    {
        switch ($type) {

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
     * @var string
     */
    private $message = '';

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
        return self::getStringFromType($this->type);
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

    public function toArray()
    {
        return parent::toArray() + array(
            'type'    => $this->getTypeString(),
            'message' => $this->message,
            'date'    => $this->date,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->type    = self::getTypeFromString($array['type']);
        $this->message = $array['message'];
        $this->date    = $array['date'];
    }
}
