<?php

namespace Mailer\Model;

/**
 * Sort constants
 */
class Sort
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
}
