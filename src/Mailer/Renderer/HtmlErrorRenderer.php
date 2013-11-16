<?php

namespace Mailer\Renderer;

class HtmlErrorRenderer extends HtmlRenderer
{
    public function findTemplate()
    {
        return 'views/error.phtml';
    }

    public function prepareVariables($return)
    {
        if ($return instanceof \Exception) {
            return array(
                'e' => $return,
            );
        } else {
            return $return;
        }
    }

    public function needsSerialize()
    {
        return false;
    }
}
