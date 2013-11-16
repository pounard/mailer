<?php

namespace Mailer\View;

use Mailer\Model\ArrayConverter;

class JsonRenderer implements RendererInterface
{
    public function render(View $view)
    {
        $converter = new ArrayConverter();

        return json_encode(
            $converter->serialize(
                $view->getValues()
            )
        );
    }

    public function getContentType()
    {
        return "application/json";
    }
}
