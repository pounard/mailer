<?php

namespace Mailer\Security;

/**
 * User account
 */
class Account
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * Default constructor
     *
     * @param int $id
     * @param string $username
     * @param string $password
     */
    public function __construct($id, $username, $password)
    {
        $this->id;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get identifier
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
