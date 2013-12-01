<?php

namespace Mailer\View;

use Mailer\Error\LogicError;
use Mailer\Error\TechnicalError;
use Mailer\View\Helper\TemplateFactory;

class Template
{
    /**
     * @var View
     */
    protected $view;

    /**
     * @var TemplateFactory
     */
    protected $helpers;

    /**
     * Default constructor
     *
     * @param View $view
     * @param TemplateFactory $helpers
     */
    public function __construct(View $view, TemplateFactory $helpers)
    {
        $this->view = $view;
        $this->helpers = $helpers;
    }

    /**
     * Find template file path
     *
     * @return string
     */
    public function findFile()
    {
        if (!$template = $this->view->getTemplate()) {
            $template = 'app/debug';
        }

        return 'views/' . $template . '.phtml';
    }

    /**
     * Allow templates to use helpers
     *
     * @param string $name
     * @param array $arguments
     */
    public function __call($name , array $arguments)
    {
        return call_user_func_array($this->helpers->getInstance($name), $arguments);
    }

    /**
     * Render template and fetch output
     *
     * @param mixed $values
     * @param string $template
     */
    public function render()
    {
        if (!$file = $this->findFile()) {
            throw new TechnicalError(sprintf("Could not find any template to use"));
        }

        ob_start();
        extract($this->view->getValues());

        if (!(bool)include $file) {
            ob_flush(); // Never leave an opened resource

            throw new LogicError(sprintf("Could not find template '%s'", $template));
        }

        return ob_get_clean();
    }

    public function __toString()
    {
        return $this->render();
    }
}
