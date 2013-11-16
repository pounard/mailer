<?php

namespace Mailer\View;

interface RendererInterface
{
    /**
     * Render content
     *
     * @param mixed $return
     */
    public function render(View $view);

    /**
     * Get return content mime type
     *
     * @return string
     */
    public function getContentType();
}
