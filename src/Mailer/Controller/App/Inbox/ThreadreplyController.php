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
use Mailer\Model\Thread;
use Mailer\View\View;

use Zend\Validator\Digits as DigitsValidator;

class ThreadReplyController extends AbstractController
{
    /**
     * Get form
     *
     * @return \Mailer\Form\Form
     */
    protected function getForm()
    {
        $form = new Form();
        $form->addElement(array(
            'name' => 'subject',
        ));
        $form->addElement(array(
            'name' => 'mailbox',
        ));
        $form->addElement(array(
            'name' => 'body',
        ));
        $form->addElement(array(
            'name' => 'threadUid',
            'validators' => new DigitsValidator(),
        ));

        return $form;
    }

    public function postAction(RequestInterface $request, array $args)
    {
        $form     = $this->getForm();
        $values   = $request->getContent();
        $index    = $this->getContainer()->getIndex();
        $messager = $this->getContainer()->getMessager();
        $mailbox  = null;
        $folder   = null;

        if ($form->validate($values)) {

            $mailbox = $origin = $this
                ->getContainer()
                ->getIndex()
                ->getMailboxIndex($values['mailbox']);

            // Run a 404 if mailbox, thread or any mail is not found
            $thread = $mailbox->getThread($values['threadUid'], true);
            $origin = $mailbox->getMail($thread->getUid());
            $mails  = $mailbox->getMails(array_keys($thread->getUidMap()));

            $to = array();
            foreach ($mails as $mail) {
                $to[] = $mail->getFrom();
            }
            $to = array_unique($to);

            // Build full mail and multipart data
            $multipart = new Multipart();
            $part = new Part();
            $part->setType('text');
            $part->setSubtype('plain');
            $part->setParameters(array('charset' => $request->getCharset()));
            $part->setContents($values['body']);
            // 8bit is a good default used with UTF-8
            // http://stackoverflow.com/questions/2265579/php-e-mail-encoding
            $part->setEncoding(Part::ENCODING_8BIT);
            $multipart->appendPart($part);

            $mail = new Mail();
            $mail->fromArray(array(
                'to'           => $to,
                'subject'      => $values['subject'],
                'structure'    => $multipart,
                'inReplyToUid' => $origin->getUid(),
                'inReplyTo'    => $origin->getMessageId(),
            ));

            $name = $index->sendMail($mail, $mailbox->getName());
            $messager->addMessage(sprintf("Mail writen into '%s'", $name), Message::TYPE_SUCCESS);
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
