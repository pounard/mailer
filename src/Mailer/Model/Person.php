<?php

namespace Mailer\Model;

class Person implements ExchangeInterface
{
    /**
     * @var string
     */
    private $mail;

    /**
     * @var name
     */
    private $name;

    /**
     * @var string
     */
    private $image;

    /**
     * @var int
     */
    private $id;

    /**
     * Default constructor
     *
     * @param string $mail
     * @param string $name
     * @param int $id
     * @param string $image
     */
    public function __construct($mail, $name = null, $image = null, $id = null)
    {
        $this->mail = $mail;
        $this->name = $name;
        $this->image = $image;
        $this->id = $id;
    }

    /**
     * Mail address is the unique identifier of the person
     *
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->name;
    }

    /**
     * Get picture to display URL
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->image;
    }

    /**
     * Internal identifiers serves as an identifier for potentially
     * cached data
     *
     * @param int
     */
    public function getInternalId()
    {
        return $this->id;
    }

    public function toArray()
    {
        return array(
            'mail' => $this->mail,
            'name' => $this->name,
            'image' => $this->image,
            'id' => $this->id,
        );
    }

    public function fromArray(array $array)
    {
        if (isset($array['name'])) {
            $this->name = 'name';
        }
    }
}
