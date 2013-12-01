<?php

namespace Mailer\Form;

use Zend\Filter\FilterChain;
use Zend\Filter\FilterInterface;
use Zend\Validator\ValidatorChain;
use Zend\Validator\ValidatorInterface;

class Element implements ElementInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ValidatorChain
     */
    private $validators;

    /**
     * @var FilterChain
     */
    private $filters = array();

    /**
     * @var boolean
     */
    private $isMultiple = false;

    /**
     * @var boolean 
     */
    private $isRequired = false;

    /**
     * Default constructor
     *
     * @param string $name
     * @param array $validators
     * @param array $filters
     */
    public function __construct($name, $isMultiple = false, $isRequired = false)
    {
        $this->name = $name;
        $this->isMultiple = $isMultiple;
        $this->isRequired = $isRequired;
        $this->filters = new FilterChain();
        $this->validators = new ValidatorChain();
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Is the element multiple
     *
     * @return boolean
     */
    public function isMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * Is the element required
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * Add an array of filters
     *
     * @param callable[]|FilterInterface[]|string[] $filters
     *
     * @return Element
     */
    public function addFilters(array $filters)
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
        return $this;
    }

    /**
     * Add a single fitler
     *
     * @param callable|FilterInterface|string $filter
     *
     * @return Element
     */
    public function addFilter($filter)
    {
        if (is_callable($filter)) {
            $this->filters->attach($filter);
        } else if (is_string($filter)) {
            $this->filters->attachByName($filter);
        } else if ($filter instanceof FilterInterface) {
            $this->filters->attach($filter);
        } else {
            throw new \InvalidArgumentException();
        }
        return $this;
    }

    /**
     * Add an array of validators
     *
     * @param callable[]|ValidatorInterface[]|string[] $validators
     * @param boolean $breakOnFailure
     *
     * @return Element
     */
    public function addValidators(array $validators, $breakOnFailure = true)
    {
        foreach ($validators as $validator) {
            $this->addValidator($validator, $breakOnFailure);
        }
        return $this;
    }

    /**
     * Add a single validator
     *
     * @param callable|ValidatorInterface|string $validator
     * @param boolean $breakOnFailure
     *
     * @return Element
     */
    public function addValidator($validator, $breakOnFailure = true)
    {
        if (is_callable($validator)) {
            $this->validators->attach($validator, $breakOnFailure);
        } else if (is_string($validator)) {
            $this->validators->addValidator($validator, $breakOnFailure);
        } else if ($validator instanceof ValidatorInterface) {
            $this->validators->addValidator($validator, $breakOnFailure);
        } else {
            throw new \InvalidArgumentException();
        }
        return $this;
    }

    public function validate($value)
    {
        if ($this->isRequired() && empty($value)) {
            return false; // @todo Add message
        } else if (empty($value)) {
            return true;
        }

        return $this->validators->isValid($value);
    }

    public function getValidationMessages()
    {
        return $this->validators->getMessages();
    }

    public function filter($value)
    {
        return $value; // @todo Implement this
    }
}
