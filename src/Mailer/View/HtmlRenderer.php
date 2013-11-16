<?php

namespace Mailer\View;

use Mailer\Core\AbstractContainerAware;
use Mailer\Error\TechnicalError;
use Mailer\Error\LogicError;

/**
 * Container is needed here because we need site configuration for default
 * HTML variables such as site name
 */
class HtmlRenderer extends AbstractContainerAware implements RendererInterface
{
    public function findTemplate($template = null)
    {
        if (empty($template)) {
            $template = 'index';
        }

        return 'views/' . $template . '.phtml';
    }

    /**
     * Prepare variables from the view
     *
     * @param mixed $values
     *   View values
     *
     * @return array
     *   Variables for the template
     */
    public function prepareVariables($values)
    {
        if (is_array($values)) {
            $ret = $values;
        } else {
            $ret = array('content' => $values);
        }

        $container = $this->getContainer();
        $config = $container['config'];

        $ret['title'] = $config['/html/title'];
        $ret['pagetitle'] = "Server response";

        return $ret;
    }

    public function render(View $view)
    {
        $template = $view->getTemplate();

        if (!$file = $this->findTemplate($template)) {
            throw new TechnicalError(sprintf("Could not find any template to use"));
        }

        $return = $this->prepareVariables($view->getValues());

        ob_start();
        extract($return);
        if (!(bool)include $file) {
            ob_flush(); // Never leave an opened resource

            throw new LogicError(sprintf("Could not find template '%s'", $template));
        }

        return ob_get_clean();
    }

    public function getContentType()
    {
        return "application/html";
    }
}
