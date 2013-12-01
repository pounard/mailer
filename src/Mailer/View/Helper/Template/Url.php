<?php

namespace Mailer\View\Helper\Template;

use Mailer\Core\AbstractContainerAware;

class Url extends AbstractContainerAware
{
    public function __invoke($path = null, array $args = null)
    {
        // FIXME: Base path here
        return '/' . $path;
    }
}
