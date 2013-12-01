<?php

namespace Mailer\Form;

interface ElementInterface
{
    /**
     * Validate element
     *
     * @param string[]|string $value
     *   If element is a single element it will be its single value
     *   If element is depth or multiple this will contain the element
     *   values keyed by names
     *
     * @return string|boolean
     *   True if element has validated
     *   False if element has not validated and has no messages
     *   Array of string keyed by element names containing error messages
     */
    public function validate($value);

    /**
     * After a validate call get the various validation failures messages
     *
     * @return string[][]
     *   Keys are element names while values are array of messages
     */
    public function getValidationMessages();

    /**
     * Filter data
     *
     * @param string[]|string $value
     *   If element is a single element it will be its single value
     *   If element is depth or multiple this will contain the element
     *   values keyed by names
     *
     * @return string|string[]
     *   Filtered data whose structure is the same as the input value
     */
    public function filter($value);
}
