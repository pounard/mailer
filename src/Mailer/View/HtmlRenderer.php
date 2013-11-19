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
            $template = 'app/debug';
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
    protected function prepareVariables($values)
    {
        if (is_array($values)) {
            $ret = $values;
        } else {
            $ret = array('content' => $values);
        }

        $container = $this->getContainer();
        $config = $container['config'];
        $request = $container['request'];

        $ret['title'] = $config['/html/title'];
        $ret['basepath'] = $container['basepath'];
        $ret['url'] = $container['basepath'] . $request->getResource();
        $ret['session'] = $container['session'];
        $ret['account'] = $ret['session']->getAccount();
        $ret['isAuthenticated'] = $ret['session']->isAuthenticated();
        $ret['pagetitle'] = isset($values['pagetitle']) ? $values['pagetitle'] : null;

        return $ret;
    }

    /**
     * Render template and fetch output
     *
     * @param mixed $values
     * @param string $template
     */
    protected function renderTemplate($values, $template = null)
    {
        if (!$file = $this->findTemplate($template)) {
            throw new TechnicalError(sprintf("Could not find any template to use"));
        }

        ob_start();
        extract($values);

        if (!(bool)include $file) {
            ob_flush(); // Never leave an opened resource

            throw new LogicError(sprintf("Could not find template '%s'", $template));
        }

        return ob_get_clean();
    }

    public function render(View $view)
    {
        // Render the content template
        $partial = $this->renderTemplate(
            $this->prepareVariables(
                $view->getValues()
            ),
            $view->getTemplate()
        );

        // Wrap the rendering into an full HTML page
        return $this->renderTemplate(
            $this->prepareVariables(
                array('content' => $partial)
            ),
            'app/layout'
        );
    }

    public function getContentType()
    {
        return "text/html";
    }
}
