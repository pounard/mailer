<?php

namespace Mailer\Model;

/**
 * Single displayable mailbox item (thread or mail)
 */
interface ItemInterface extends ExchangeInterface
{
    /**
     * Item is a thread
     */
    const TYPE_THREAD = 'thread';

    /**
     * Item is a single mail
     */
    const TYPE_MAIL = 'mail';

    /**
     * Get mailbox name
     *
     * @return string
     */
    public function getMailbox();

    /**
     * Get primary identifier
     *
     * @return int
     */
    public function getUid();

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Get creation date
     *
     * @return \DateTime
     */
    public function getCreationDate();

    /**
     * Get last update date
     *
     * @return \DateTime
     */
    public function getLastUpdate();

    /**
     * Get message count
     *
     * @return int
     */
    public function getMessageCount();

    /**
     * Get unread count
     *
     * @return int
     */
    public function getUnseenCount();

    /**
     * Get recent count
     *
     * @return int
     */
    public function getRecentCount();

    /**
     * Get summary for display
     *
     * @return string
     */
    public function getSummary();

    /**
     * Get subject for display
     *
     * @return string
     */
    public function getSubject();

    /**
     * Get from (which has no sense when using thread)
     *
     * @return Person
     */
    public function getFrom();

    /**
     * Get to
     *
     * @return Person[]
     */
    public function getTo();

    /**
     * Is message flagged
     *
     * @return bool
     */
    public function isFlagged();
    
    /**
     * Is message marked for deletion
     *
     * @return bool
     */
    public function isDeleted();
}
