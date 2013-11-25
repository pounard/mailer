<?php

namespace Mailer\View\Helper;

/**
 * Filter collection allows to filter text with successive calls to
 * a collection of pre-defined filters
 */
class FilterCollection implements FilterInterface
{
    /**
     * @var FilterInterface[] $filter
     */
    private $filters = array();

    /**
     * Default constructor
     *
     * @param FilterInterface[] $filters
     */
    public function __construct(array $filters)
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
    }

    /**
     * Add filter
     *
     * @param FilterInterface $filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    public function filter($text)
    {
        foreach ($this->filters as $filter) {
            $text = $filter->filter($text);
        }

        return $text;
    }
}
