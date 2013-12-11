<?php

namespace Mailer\Model;

/**
 * Represent any object that can be serialized
 */
interface ExchangeInterface
{
    /**
     * Get unique object checksum for cache perishing computing
     *
     * @return string
     */
    public function getChecksum();

    /**
     * Regenerate random checksum
     */
    public function regenerateChecksum();

    /**
     * Convert the current object to array
     *
     * @return array
     */
    public function toArray();

    /**
     * Populate the object from array
     *
     * This method is not supposed to create a full object but rather edit
     * and existing one: for example folder objects will only accept a name
     * change and nothing more
     *
     * @param array $array
     */
    public function fromArray(array $array);
}
