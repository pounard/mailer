<?php

namespace Mailer\Controller\App\Inbox;

use Mailer\Controller\AbstractController;
use Mailer\Core\Message;
use Mailer\Dispatch\RequestInterface;
use Mailer\Form\Form;
use Mailer\Model\Person;
use Mailer\Model\SentMail;
use Mailer\View\View;

use Zend\Validator\EmailAddress;
use Mailer\Dispatch\Http\RedirectResponse;

class ComposeController extends AbstractController
{
    private function getForm()
    {
        $form = new Form();
        $form->addElement(array(
            'name' => 'to',
            'required' => true,
            'validators' => array(new EmailAddress()),
        ));
        /*
        $form->addElement(array(
            'name' => 'cc',
            'validators' => array(new EmailAddress()),
        ));
        $form->addElement(array(
            'name' => 'bcc',
            'validators' => array(new EmailAddress()),
        ));
         */
        $form->addElement(array(
            'name' => 'subject',
        ));
        $form->addElement(array(
            'name' => 'body',
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

            $mail = new SentMail();
            $mail->fromArray(array(
                'from' => Person::fromMailAddress("Pierre Rineau <pounard@processus.org>"), // FIXME
                'to' => array(Person::fromMailAddress($values['to'])), // FIXME Multiple recipients
                'subject' => $values['subject'],
                'bodyPlain' => $values['body'],
            ));

            $this
                ->getContainer()
                ->getIndex()
                ->sendMail($mail);

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
