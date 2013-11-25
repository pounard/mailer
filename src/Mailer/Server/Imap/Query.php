<?php

namespace Mailer\Server\Imap;

/**
 * Sort constants
 */
class Query
{
    /**
     * Sort by date
     */
    const SORT_DATE = 1;

    /**
     * Sort by date
     */
    const SORT_SEQ = 2;

    /**
     * Sort by arrival date
     */
    const SORT_ARRIVAL = 3;

    /**
     * Sort by from
     */
    const SORT_FROM = 4;

    /**
     * Sort by subject
     */
    const SORT_SUBJECT = 5;

    /**
     * Sort by to
     */
    const SORT_TO = 6;

    /**
     * Sort by CC
     */
    const SORT_CC = 7;

    /**
     * Sort by size
     */
    const SORT_SIZE = 8;

    /**
     * Ascending
     */
    const ORDER_ASC = 1;

    /**
     * Descending
     */
    const ORDER_DESC = 2;

    /**
     * No limit
     */
    const LIMIT_NONE = 0;

    /**
     * Default limit
     */
    const LIMIT_DEFAULT = 50;

    /**
     * Default offset
     */
    const OFFSET_DEFAULT = 0;

    private $limit = 20;

    private $offset = 0;

    private $sort = self::SORT_SEQ;

    private $order = self::ORDER_ASC;

    public function __construct(
        $limit    = self::LIMIT_DEFAULT,
        $offset   = self::OFFSET_DEFAULT,
        $sort     = self::SORT_SEQ,
        $order    = self::ORDER_DESC)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sort = $sort;
        $this->order = $order;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getSort()
    {
        return $this->sort;
    }

    public function getOrder()
    {
        return $this->order;
    }
}
