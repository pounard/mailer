<?php

namespace Mailer\View\Helper\Template;

use Mailer\Core\AbstractContainerAware;

class Messages extends AbstractContainerAware
{
    public function __invoke()
    {
        return $this
            ->getContainer()
            ->getMessager()
            ->getMessages();
    }
}
