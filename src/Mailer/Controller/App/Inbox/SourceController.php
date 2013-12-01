<?php

namespace Mailer\Controller\App\Inbox;

use Mailer\Controller\AbstractController;
use Mailer\Dispatch\RequestInterface;
use Mailer\View\View;
use Mailer\Error\NotImplementedError;
use Mailer\Error\NotFoundError;

class SourceController extends AbstractController
{
    public function getAction(RequestInterface $request, array $args)
    {
        if (2 !== count($args)) {
            throw new NotFoundError();
        }

        $source = $this
            ->getContainer()
            ->getIndex()
            ->getMailboxIndex($args[0])
            ->getMailSource($args[1], 0);

        $source = htmlspecialchars(
            $source,
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED,
            $this->getContainer()->getDefaultCharset()
        );

        return new View(
            array(
                'source' => $source,
            ),
            'app/inbox/source'
        );
    }
}
