<?php

namespace Mailer\View;

/**
 * Represent what needs to be rendered
 *
 * This object may carry a template name, but template can be null case in
 * which the renderer will act upon a default template. In most case when
 * doing REST calls template has no sense.
 */
class View
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var mixed
     */
    private $values;

    /**
     * Default constructor
     *
     * @param mixed $values
     * @param string $template
     */
    public function __construct($values, $template = null)
    {
        $this->values = $values;
        $this->template = $template;
    }

    /**
     * Give a chance to alter template after creation
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get values
     *
     * @return mixed
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Is the view empty
     */
    public function isEmpty()
    {
        return empty($this->values);
    }
}
