<?php

namespace Mailer\View;

use Mailer\Core\AbstractContainerAware;
use Mailer\Dispatch\RequestInterface;
use Mailer\Error\LogicError;
use Mailer\Model\ArrayConverter;

class JsonRenderer extends AbstractContainerAware implements RendererInterface
{
    public function render(View $view, RequestInterface $request)
    {
        $values = array(
            'data'     => $view->getValues(),
            'status'   => "success",
            'messages' => $this->getContainer()->getMessager()->getMessages(),
        );

        $converter = new ArrayConverter();
        $ret = json_encode($converter->serialize($values));

        if (false === $ret) {
            $code = json_last_error();

            switch ($code) {

                case JSON_ERROR_CTRL_CHAR:
                    $message = "Unexpected control character found";
                    break;

                case JSON_ERROR_DEPTH:
                    $message = "Maximum stack depth exceeded";
                    break;

                case JSON_ERROR_NONE:
                    $message = "No error";
                    break;

                case JSON_ERROR_STATE_MISMATCH:
                    $message = "Underflow or the modes mismatch";
                    break;

                case JSON_ERROR_SYNTAX:
                    $message = "Syntax error, malformed JSON";
                    break;

                case JSON_ERROR_UTF8:
                    $message = "Malformed UTF-8 characters, possibly incorrectly encoded";
                    break;

                default:
                    $message = "Unknown error";
                    break;
            }

            throw new LogicError($message, $code);
        }

        return $ret;
    }

    public function getContentType()
    {
        return "application/json";
    }
}
