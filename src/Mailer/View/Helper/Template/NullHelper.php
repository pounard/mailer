<?php

namespace Mailer\View\Helper\Template;

class NullHelper
{
    public function __invoke()
    {
        return "";
    }
}
