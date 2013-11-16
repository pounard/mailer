<?php

namespace Mailer\Renderer;

use Mailer\Error\TechnicalError;

class HtmlRenderer implements RendererInterface
{
    public function findTemplate()
    {
        return 'views/index.phtml';
    }

    public function prepareVariables($return)
    {
        return $return;
    }

    public function render($return)
    {
        if (!$template = $this->findTemplate()) {
            throw new TechnicalError(sprintf("Could not find any template to use"));
        }

        $return = $this->prepareVariables($return);

        if (is_array($return)) {
            extract($return);
        } // Else there will only the $return variable

        ob_start();
        include $template;
        return ob_get_clean();
    }

    public function getContentType()
    {
        return "application/html";
    }
}
