<?php

namespace Mailer\Server;

use Mailer\Error\LogicError;

interface ServerInterface
{
    /**
     * Get host
     *
     * @return string
     */
    public function getHost();

    /**
     * Get port
     *
     * @return int
     */
    public function getPort();

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword();

    /**
     * Reset this object credentials
     */
    public function setCredentials($username, $password, $reconnect = false);

    /**
     * Is connection secure
     *
     * @return boolean
     */
    public function isSecure();

    /**
     * Does it accepts invalid certificates
     *
     * @return boolean
     */
    public function acceptsInvalidCertificate();

    /**
     * Get default connection port for the protocol
     *
     * @param boolean $isSecure
     */
    public function getDefaultPort($isSecure);

    /**
     * Force connection to open
     *
     * @return boolean
     */
    public function connect();

    /**
     * Is this server currently connected
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Set options
     *
     * @param array $options
     */
    public function setOptions(array $options);

    /**
     * Get options this server has been created with
     *
     * @return array
     */
    public function getOptions();
}
