<?php

namespace Mailer\Model;

/**
 * Represents a single thread
 *
 * Those instances will not carry mail information but only needed headers
 * for the client to display the thread summary on screen
 */
class Thread implements ExchangeInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $summary;

    /**
     * @var Person[]
     */
    private $persons = array();

    /**
     * @var \DateTime
     */
    private $startedDate;

    /**
     * @var \DateTime
     */
    private $lastUpdate;

    /**
     * @var int
     */
    private $messageCount;

    /**
     * @var int
     */
    private $recentCount;

    /**
     * @var int
     */
    private $unseenCount;

    /**
     * Get thread id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get title of the thread from any mail subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get short text summary of the thread found in any mail
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Get persons that participated to this thread
     *
     * @return Person[]
     */
    public function getPersons()
    {
        return $this->persons;
    }

    /**
     * Get date this thread started
     *
     * @return \DateTime
     */
    public function getStartedDate()
    {
        return $this->startedDate;
    }

    /**
     * Get date this thread has been last modified
     *
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Get message count
     *
     * @return int
     */
    public function getMessageCount()
    {
        return $this->messageCount;
    }

    /**
     * Get recent message count
     *
     * @return int
     */
    public function getRecentCount()
    {
        return $this->recentCount;
    }

    /**
     * Get unseen message count
     *
     * @return int
     */
    public function getUnseenCount()
    {
        return $this->unseenCount;
    }

    public function toArray()
    {
        return array(
            'id'           => $this->id,
            'subject'      => $this->subject,
            'summary'      => $this->summary,
            'persons'      => $this->persons,
            'startedDate'  => $this->startedDate,
            'lastUpdate'   => $this->lastUpdate,
            'messageCount' => $this->messageCount,
            'recentCount'  => $this->recentCount,
            'unseenCount'  => $this->unseenCount,
        );
    }

    public function fromArray(array $array)
    {
        $array += array(
            'id'           => -1,
            'subject'      => '',
            'summary'      => null,
            'persons'      => array(),
            'startedDate'  => null,
            'lastUpdate'   => null,
            'messageCount' => 0,
            'recentCount'  => 0,
            'unseenCount'  => 0,
        );

        $this->id           = (int)$array['id'];
        $this->subject      = $array['subject'];
        $this->summary      = $array['summary'];
        $this->persons      = $array['persons'];
        $this->startedDate  = $array['startedDate'];
        $this->lastUpdate   = $array['lastUpdate'];
        $this->messageCount = (int)$array['messageCount'];
        $this->recentCount  = (int)$array['recentCount'];
        $this->unseenCount  = (int)$array['unseenCount'];
    }
}
