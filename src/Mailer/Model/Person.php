<?php

namespace Mailer\Model;

class Person implements ExchangeInterface
{
    /**
     * Build value array from formatted mail address
     *
     * @param string $mailString
     *
     * @return array
     *   Array with 'mail' and 'name' values
     */
    static public function mailToArray($mailString)
    {
        $matches = array();
        if (preg_match('/^(|")([^\<]*)(|")(|\<([^\>]*)\>)$/', trim($mailString), $matches)) {
            if (!empty($matches[5])) { // There is something weird there...
                return array('mail' => $matches[5], 'name' => $matches[2]);
            } else {
                return array('mail' => $matches[0], 'name' => null);
            }
        } else {
            return array('mail' => $mailString, 'name' => null);
        }
    }

    /**
     * Build instance from formatted mail address
     *
     * @param string $mailString
     *
     * @return Person
     */
    static public function fromMailAddress($mailString)
    {
        $instance = new self();
        $instance->fromArray(self::mailToArray($mailString));

        return $instance;
    }

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
            'mail'  => $this->mail,
            'name'  => $this->name,
            'image' => $this->image,
            'id'    => $this->id,
        );
    }

    public function fromArray(array $array)
    {
        $array += array(
            'mail'  => null,
            'name'  => null,
            'image' => null,
            'id'    => null,
        );

        $this->mail  = $array['mail'];
        $this->name  = $array['name'];
        $this->image = $array['image'];
        $this->id    = $array['id'];
    }
}
