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

        $ret['title'] = $config['html/title'];
        $ret['basepath'] = $request->getBasePath();
        $ret['url'] = $ret['basepath'] . $request->getResource();
        $ret['session'] = $session;
        $ret['account'] = $session->getAccount();
        $ret['isAuthenticated'] = $session->isAuthenticated();
        $ret['pagetitle'] = isset($ret['pagetitle']) ? $ret['pagetitle'] : null;

        return $ret;
    }

    public function render(View $view, RequestInterface $request)
    {
        $templateFactory = $this->getContainer()->getTemplateFactory();

        // Current controller return
        $template = new Template(
            new View(
                $this->prepareVariables($request, $view->getValues()),
                $view->getTemplate()
            ),
            $templateFactory
        );

        // Main layout
        $layout = new Template(
            new View(
                $this->prepareVariables($request, $template),
                'app/layout'
            ),
            $templateFactory
        );

        return $layout;
    }

    public function getContentType()
    {
        return "text/html";
    }
}
