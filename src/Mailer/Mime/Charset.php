<?php

namespace Mailer\Mime;

/**
 * Convert content
 */
class Charset
{
    /**
     * Default working charset
     *
     * @var string
     */
    static private $defaultCharset = null;

    /**
     * Set default charset
     *
     * @param string $charset
     */
    static public function setDefaultCharset($charset)
    {
        self::$defaultCharset = $charset;
    }

    /**
     * Get default charset
     *
     * @return string
     */
    static public function getDefaultCharset()
    {
        if (null === self::$defaultCharset) {
            return mb_internal_encoding();
        }
        return self::$defaultCharset;
    }

    /**
     * Convert content
     *
     * @param string $string
     *   String to convert
     * @param string $from
     *   From charset
     * @param string $to
     *   To charset
     *
     * @return string
     *   Converted string
     */
    static public function convert($string, $from, $to = null)
    {
        if (null === $to) { 
            $to = self::getDefaultCharset();
        }

        // Remove NULL characters if any.
        // See Roundcube bug #1486189.
        if (strpos($string, "\x00") !== false) {
            $string = str_replace("\x00", '', $string);
        }

        // See Roundcube method documentation, charset might be malformed.
        $to   = \rcube_charset::parse_charset($to);
        $from = \rcube_charset::parse_charset($from);

        /*
         * Is this really necessary?
         *
        if ('US-ASCII' === $charset && preg_match('/^(text|message)$/', $type)) {
            // try to extract charset information from HTML meta tag (#1488125)
            if (false !== strpos($subtype, 'html') && preg_match('/<meta[^>]+charset=([a-z0-9-_]+)/i', $body, $matches)) {
                $charset = strtoupper($matches[1]);
            }
        }
         */

        return mb_convert_encoding($string, $to, $from);
    }
}
