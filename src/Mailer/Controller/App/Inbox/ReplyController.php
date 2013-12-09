<?php

namespace Mailer\Controller\App\Inbox;

use Mailer\Controller\AbstractController;
use Mailer\Core\Message;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\NotFoundError;
use Mailer\Form\Form;
use Mailer\Mime\Part;
use Mailer\Mime\Multipart;
use Mailer\Model\Mail;
use Mailer\Model\Person;
use Mailer\View\View;

use Zend\Validator\Digits as DigitsValidator;

class ReplyController extends ComposeController
{
    public function getAction(RequestInterface $request, array $args)
    {
        $form = $this->getForm();

        if (count($args) !== 2) {
            throw new NotFoundError();
        }

        // Run a 404 if mail is not found
        $origin = $this
            ->getContainer()
            ->getIndex()
            ->getMailboxIndex($args[0])
            ->getMail($args[1]);

        return new View(
            array(
                'defaults'     => $form->getDefaultValues(),
                'placeholders' => $form->getPlaceHolders(),
                'origin'       => $origin,
            ),
            'app/inbox/reply'
        );
    }

    public function postAction(RequestInterface $request, array $args)
    {
        $form     = $this->getForm();
        $values   = $request->getContent();
        $index    = $this->getContainer()->getIndex();
        $messager = $this->getContainer()->getMessager();
        $folder   = null;

        if ($form->validate($values)) {

            // Run a 404 if mail is not found
            $origin = $this
                ->getContainer()
                ->getIndex()
                ->getMailboxIndex($args[0])
                ->getMail($args[1]);

            $default = array(
                'inReplyToUid' => $args[1],
                'inReplyTo'    => $origin->getMessageId(),
            );

            $mail = $this
                ->getMailFromValues(
                    $request,
                    array_merge(
                        $form->filter($values),
                        $default
                    )
                );

            $mailbox = $index->sendMail($mail, $args[0]);
            $messager->addMessage(sprintf("Mail writen into '%s'", $mailbox), Message::TYPE_SUCCESS);
            $messager->addMessage("Your message has been sent", Message::TYPE_SUCCESS);

            return new RedirectResponse('app/inbox');

        } else {
            $messager = $this->getContainer()->getMessager();
            if ($messages = $form->getValidationMessages()) {
                foreach ($messages as $message) {
                    $messager->addMessage($message, Message::TYPE_ERROR);
                }
            } else {
                $messager->addMessage("Validation errors", Message::TYPE_ERROR);
            }

            return $this->getAction($request, $args);
        }
    }
}
