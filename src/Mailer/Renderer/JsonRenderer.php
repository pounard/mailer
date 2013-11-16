<?php

namespace Mailer\Renderer;

class JsonRenderer implements RendererInterface
{
    public function render($return)
    {
        return json_encode($return);
    }

    public function getContentType()
    {
        return "application/json";
    }

    public function needsSerialize()
    {
        return true;
    }
}
