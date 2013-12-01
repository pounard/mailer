<?php

namespace Mailer\Form;

/**
 * This implementation of form is very simple and fits with basic needs.
 *
 * It does not include rendering, its only job is validation from values.
 */
class Form implements ElementInterface
{
    /**
     * @var Element[]
     */
    protected $elements = array();

    /**
     * Create and attach new element
     *
     * @param string $name
     * @param boolean $isMultiple
     * @param boolean $isRequired
     *
     * @return Element
     */
    public function createElement($name, $isMultiple = false, $isRequired = false)
    {
        $element = new Element($name, $isMultiple, $isRequired);
        $this->attachElement($element);

        return $element;
    }

    /**
     * Spawn and attach element from array definition
     *
     * @param array $array
     */
    private function addElementFromArray($array)
    {
        if (empty($array['name'])) {
            throw new \InvalidArgumentException();
        }
        if (empty($array['multiple'])) {
            $multiple = false;
        } else {
            $multiple = true;
        }
        if (empty($array['required'])) {
            $required = false;
        } else {
            $required = true;
        }

        $element = $this->createElement($array['name'], $multiple, $required);

        if (!empty($array['validators'])) {
            $element->addValidators($array['validators']);
        }
        if (!empty($array['filters'])) {
            $element->addFilters($array['filters']);
        }
    }

    /**
     * Attach element instance
     *
     * @param Element $element
     */
    private function attachElement(Element $element)
    {
        $this->elements[$element->getName()] = $element;
    }

    /**
     * Add element to this form
     *
     * @param array|string|Element $element
     */
    public function addElement($element)
    {
        if (is_string($element)) {
            $this->createElement($string);
        } else if (is_array($element)) {
            $this->addElementFromArray($element);
        } else if ($element instanceof Element) {
            $this->attachElement($element);
        } else {
            throw new \InvalidArgumentException();
        }
    }

    public function validate($value)
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException();
        }

        $success = true;

        foreach ($this->elements as $name => $element) {
            if (!isset($value[$name])) {
                $value[$name] = null;
            }
            $success = $success && $element->validate($value[$name]);
        }

        return $success;
    }

    public function getValidationMessages()
    {
        $ret = array();

        foreach ($this->elements as $name => $element) {
            if ($messages = $element->getValidationMessages()) {
                $ret[$name] = $messages;
            }
        }

        return $ret;
    }

    public function filter($value)
    {
        return array_intersect_key($value, $this->elements);
    }
}
