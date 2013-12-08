<?php

namespace Mailer\Controller\App\Inbox;

use Mailer\Controller\AbstractController;
use Mailer\Core\Message;
use Mailer\Dispatch\Http\RedirectResponse;
use Mailer\Dispatch\RequestInterface;
use Mailer\Form\Form;
use Mailer\Model\Mail;
use Mailer\Model\Person;
use Mailer\Validator\EmailAddress;
use Mailer\View\View;

use Zend\Validator\Digits as DigitsValidator;
use Mailer\Mime\Part;
use Mailer\Mime\Multipart;
use Mailer\Error\NotFoundError;

class ComposeController extends AbstractController
{
    protected function getForm()
    {
        $form = new Form();
        $form->addElement(array(
            'name' => 'to',
            'required' => true,
            'validators' => new EmailAddress(),
        ));
        /*
        $form->addElement(array(
            'name' => 'cc',
            'validators' => new EmailAddress(),
        ));
        $form->addElement(array(
            'name' => 'bcc',
            'validators' => new EmailAddress(),
        ));
         */
        $form->addElement(array(
            'name' => 'subject',
        ));
        $form->addElement(array(
            'name' => 'mailbox',
        ));
        $form->addElement(array(
            'name' => 'inReplyToUid',
        ));
        $form->addElement(array(
            'name' => 'inReplyTo',
        ));
        $form->addElement(array(
            'name' => 'body',
        ));
        $form->addElement(array(
            'name' => 'inReplyToUid',
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
        $folder   = null;

        if ($form->validate($values)) {

            $multipart = new Multipart();

            $part = new Part();
            $part->setType('text');
            $part->setSubtype('plain');
            $part->setParameters(array('charset' => $request->getCharset()));
            $part->setContents($values['body']);
            $part->setEncoding(Part::ENCODING_QUOTEDPRINTABLE);
            $multipart->appendPart($part);

            $mailValues = array(
                'to' => array(Person::fromMailAddress($values['to'])), // FIXME Multiple recipients
                'subject' => $values['subject'],
                'structure' => $multipart,
            );

            // Ensure the folder exists
            if (!empty($values['mailbox'])) {
                $mailbox = $values['mailbox'];
                try {
                    $folder = $index->getMailboxIndex($mailbox);
                } catch (NotFoundError $e) {
                    $messager->addMessage(sprintf("Mailbox '%s' does not exist", $mailbox), Message::TYPE_WARNING);
                    $mailbox = null;
                }
            } else {
                $mailbox = null;
            }

            // There are still some missing bits
            if (!empty($values['inReplyToUid'])) {
                $mailValues['inReplyToUid'] = $values['inReplyToUid'];
                if ($folder && empty($values['inReplyTo'])) {
                    // Attempt to load the original message from uid and folder
                    // in order to set the correct message identifier, if we cant
                    // I am so sorry for you...
                    try {
                        $original = $folder->getMail($values['inReplyToUid']);
                        $values['inReplyTo'] = $original->getMessageId();
                    } catch (NotFoundError $e) {}
                }
            }
            if (!empty($values['inReplyTo'])) {
                $mailValues['inReplyTo'] = $values['inReplyTo'];
            }

            $mail = new Mail();
            $mail->fromArray($mailValues);

            $mailbox = $index->sendMail($mail, $mailbox);
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
