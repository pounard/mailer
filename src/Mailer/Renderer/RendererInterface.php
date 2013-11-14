<?php

namespace Mailer\Renderer;

interface RendererInterface
{
    /**
     * Render content
     *
     * @param mixed $return
     */
    public function render($return);
}
