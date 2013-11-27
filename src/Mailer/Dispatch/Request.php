<?php

namespace Mailer\Dispatch;

final class Request
{
    /**
     * GET
     */
    const METHOD_GET = 0;

    /**
     * POST
     */
    const METHOD_POST = 1;

    /**
     * PUT
     */
    const METHOD_PUT = 2;

    /**
     * DELETE
     */
    const METHOD_DELETE = 3;

    /**
     * PATCH
     */
    const METHOD_PATCH = 4;

    /**
     * Convert internal method constant to comprehensible string
     *
     * @param int $method
     *
     * @return string
     */
    static public function methodToString($method)
    {
        switch ($method) {

            case self::METHOD_DELETE:
                return 'DELETE';

            case self::METHOD_GET:
                return 'GET';

            case self::METHOD_POST:
                return 'POST';

            case self::METHOD_PUT:
                return 'PUT';

            case self::METHOD_PATCH:
                return 'PATCH';

            default:
                return (string)$method;
        }
    }
}
