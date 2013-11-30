<?php

namespace Mailer\View;

class ErrorView extends View
{
    /**
     * @var Exception
     */
    private $exception;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $code;

    /**
     * Default constructor
     *
     * @param int|string|Exception $exception
     */
    public function __construct($exception)
    {
        if (is_int($exception)) {
            $this->code = $exception;
            $this->message = "Error";
        } else if (is_string($exception)) {
            $this->code = 500;
            $this->message = $exception;
        } else if ($exception instanceof \Exception) {
            $this->code = $exception->getCode();
            $this->message = $exception->getMessage();
        }

        parent::__construct(array(
            'code' => $this->code,
            'message' => $this->message,
            'exception' => $this->exception,
        ), 'app/error');
    }
}
