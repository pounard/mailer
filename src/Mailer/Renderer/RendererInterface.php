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

    /**
     * Get return content mime type
     *
     * @return string
     */
    public function getContentType();
}
