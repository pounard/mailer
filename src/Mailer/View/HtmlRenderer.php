<?php

namespace Mailer\View;

use Mailer\Core\AbstractContainerAware;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Error\TechnicalError;

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
    protected function prepareVariables(RequestInterface $request, $values)
    {
        if (is_array($values)) {
            $ret = $values;
        } else {
            $ret = array('content' => $values);
        }

        $container = $this->getContainer();
        $session = $container->getSession();
        $config = $container->getConfig();

        $ret['title'] = $config['html']['title'];
        $ret['basepath'] = $request->getBasePath();
        $ret['url'] = $ret['basepath'] . $request->getResource();
        $ret['session'] = $session;
        $ret['account'] = $session->getAccount();
        $ret['isAuthenticated'] = $session->isAuthenticated();
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

    public function render(View $view, RequestInterface $request)
    {
        // Render the content template
        $partial = $this->renderTemplate(
            $this->prepareVariables($request, $view->getValues()),
            $view->getTemplate()
        );

        // Wrap the rendering into an full HTML page
        return $this->renderTemplate(
            $this->prepareVariables($request, array('content' => $partial)),
            'app/layout'
        );
    }

    public function getContentType()
    {
        return "text/html";
    }
}
