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

class ComposeController extends AbstractController
{
    private function getForm()
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
            'name' => 'body',
        ));
        $form->addElement(array(
            'name' => 'inReplyToUid',
            'validators' => new DigitsValidator(),
        ));

        return $form;
    }

    public function getAction(RequestInterface $request, array $args)
    {
        return new View(array('form' => $request->getContent()), 'app/inbox/compose');
    }

    public function postAction(RequestInterface $request, array $args)
    {
        $form = $this->getForm();
        $values = $request->getContent();

        if ($form->validate($values)) {

            $multipart = new Multipart();

            $part = new Part();
            $part->setType('text');
            $part->setSubtype('plain');
            $part->setParameters(array('charset' => $request->getCharset()));
            $part->setContents($values['body']);
            $part->setEncoding(Part::ENCODING_QUOTEDPRINTABLE);
            $multipart->appendPart($part);

            $mail = new Mail();
            $mail->fromArray(array(
                'to' => array(Person::fromMailAddress($values['to'])), // FIXME Multiple recipients
                'subject' => $values['subject'],
                'structure' => $multipart,
            ));

            $this
                ->getContainer()
                ->getIndex()
                ->sendMail($mail);

            $part->setEncoding(Part::ENCODING_BASE64);
            $multipart->writeEncodedMime("/tmp/testmail");

            $messager = $this
                ->getContainer()
                ->getMessager()
                ->addMessage("Mail write into " . $name, Message::TYPE_SUCCESS);

            $messager = $this
                ->getContainer()
                ->getMessager()
                ->addMessage("Your message has been sent", Message::TYPE_SUCCESS);

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
