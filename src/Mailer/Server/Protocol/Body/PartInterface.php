<?php

namespace Mailer\Server\Protocol\Body;

/**
 * This interface is not RFC compliant but allows typing of multipart
 * elements
 */
interface PartInterface
{
    /**
     * When message is not multipart fetch content won't be the same
     * command, this contanst indicates to the fetch callback it should
     * not trust the index but fetch the root part instead
     */
    const INDEX_ROOT = '';

    /**
     * Index separator for FETCH queries
     */
    const INDEX_SEPARATOR = '.';

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set substype
     *
     * @param string $subtype
     */
    public function setSubtype($subtype);

    /**
     * Get subtype
     *
     * @return string
     */
    public function getSubtype();

    /**
     * Set index in parent multipart
     *
     * @param int $index
     */
    public function setIndex($index);

    /**
     * Get index in parent multipart
     *
     * @return int
     */
    public function getIndex();
}
