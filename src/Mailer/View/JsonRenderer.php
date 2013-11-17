<?php

namespace Mailer\View;

use Mailer\Model\ArrayConverter;
use Mailer\Error\LogicError;

class JsonRenderer implements RendererInterface
{
    public function render(View $view)
    {
        $converter = new ArrayConverter();

        $ret = json_encode(
            $converter->serialize(
                $view->getValues()
            )
        );

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
