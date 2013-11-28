<?php

namespace Mailer\View;

use Mailer\Dispatch\RequestInterface;

class NullRenderer implements RendererInterface
{
    public function render(View $view, RequestInterface $request)
    {
        return null;
    }

    public function getContentType()
    {
        return null;
    }
}
