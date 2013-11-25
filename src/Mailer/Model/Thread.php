<?php

namespace Mailer\Model;

/**
 * Represents a single thread
 *
 * Those instances will not carry mail information but only needed headers
 * for the client to display the thread summary on screen
 */
class Thread extends AbstractItem
{
    /**
     * @var array
     */
    private $uidMap = array();

    /**
     * @var Person[]
     */
    private $persons = array();

    /**
     * Get uid map
     *
     * @return array
     *   Keys are single envelope uid while values are direct node parent
     *   uid, some nodes might be orphan but yet in thread case in which
     *   parent is null
     *   The root node is included too, root node will always have the same
     *   uid as the thread id
     */
    public function getUidMap()
    {
        return $this->uidMap;
    }

    /**
     * Get all parties involved in the thread
     *
     * @return Person[]
     */
    public function getPersons()
    {
        return $this->persons;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'uidMap'  => $this->uidMap,
            'persons' => $this->persons,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        $this->uidMap  = (array)$array['uidMap'];
        $this->persons = (array)$array['persons'];
    }
}
