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

        /*
        $headers = $RCMAIL->storage->get_message_headers($uid);
        $charset = $headers->charset ? $headers->charset : $CONFIG['default_charset'];
        header("Content-Type: text/plain; charset={$charset}");
        
        if (!empty($_GET['_save'])) {
          $subject = rcube_mime::decode_header($headers->subject, $headers->charset);
          $filename = ($subject ? $subject : $RCMAIL->config->get('product_name', 'email')) . '.eml';
          $browser = $RCMAIL->output->browser;
        
          if ($browser->ie && $browser->ver < 7)
            $filename = rawurlencode(abbreviate_string($filename, 55));
          else if ($browser->ie)
            $filename = rawurlencode($filename);
          else
            $filename = addcslashes($filename, '"');
        
          header("Content-Length: {$headers->size}");
          header("Content-Disposition: attachment; filename=\"$filename\"");
        }
        
        $RCMAIL->storage->print_raw_body($uid, empty($_GET['_save']));
         */
    }
}
