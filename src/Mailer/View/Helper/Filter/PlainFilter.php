<?php

namespace Mailer\View\Helper\Filter;

use Mailer\Core\AbstractContainerAware;
use Mailer\View\Helper\FilterInterface;

class PlainFilter extends AbstractContainerAware implements FilterInterface
{
    /**
     * Smart line ending conversion
     *
     * This code comes from Drupal 7, credits goes to its authors.
     *
     * @param string $text
     *
     * @return string
     */
    public function autoParagraph($text)
    {
        // All block level tags
        $block = '(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|p|h[1-6]|hr)';

        // Split at opening and closing PRE, SCRIPT, STYLE, OBJECT, IFRAME tags
        // and comments. We don't apply any processing to the contents of these tags
        // to avoid messing up code. We look for matched pairs and allow basic
        // nesting. For example:
        // "processed <pre> ignored <script> ignored </script> ignored </pre> processed"
        $chunks = preg_split('@(<!--.*?-->|</?(?:pre|script|style|object|iframe|!--)[^>]*>)@i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        // Note: PHP ensures the array consists of alternating delimiters and literals
        // and begins and ends with a literal (inserting NULL as required).
        $ignore = false;
        $ignoretag = '';
        $output = '';
        foreach ($chunks as $i => $chunk) {
            if ($i % 2) {
                // Opening or closing tag?
                $open = ($chunk[1] != '/');
                list($tag) = preg_split('/[ >]/', substr($chunk, 2 - $open), 2);
                if (!$ignore) {
                    if ($open) {
                        $ignore = true;
                        $ignoretag = $tag;
                    }
                } else if (!$open && $ignoretag == $tag) { // Only allow a matching tag to close it.
                    $ignore = false;
                    $ignoretag = '';
                }
            } else if (!$ignore) {
                $chunk = preg_replace('|\n*$|', '', $chunk) . "\n\n"; // just to make things a little easier, pad the end
                $chunk = preg_replace('|<br />\s*<br />|', "\n\n", $chunk);
                $chunk = preg_replace('!(<' . $block . '[^>]*>)!', "\n$1", $chunk); // Space things out a little
                $chunk = preg_replace('!(</' . $block . '>)!', "$1\n\n", $chunk); // Space things out a little
                $chunk = preg_replace("/\n\n+/", "\n\n", $chunk); // take care of duplicates
                $chunk = preg_replace('/^\n|\n\s*\n$/', '', $chunk);
                $chunk = '<p>' . preg_replace('/\n\s*\n\n?(.)/', "</p>\n<p>$1", $chunk) . "</p>\n"; // make paragraphs, including one at the end
                $chunk = preg_replace("|<p>(<li.+?)</p>|", "$1", $chunk); // problem with nested lists
                $chunk = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $chunk);
                $chunk = str_replace('</blockquote></p>', '</p></blockquote>', $chunk);
                $chunk = preg_replace('|<p>\s*</p>\n?|', '', $chunk); // under certain strange conditions it could create a P of entirely whitespace
                $chunk = preg_replace('!<p>\s*(</?' . $block . '[^>]*>)!', "$1", $chunk);
                $chunk = preg_replace('!(</?' . $block . '[^>]*>)\s*</p>!', "$1", $chunk);
                $chunk = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $chunk); // make line breaks
                $chunk = preg_replace('!(</?' . $block . '[^>]*>)\s*<br />!', "$1", $chunk);
                $chunk = preg_replace('!<br />(\s*</?(?:p|li|div|th|pre|td|ul|ol)>)!', '$1', $chunk);
                $chunk = preg_replace('/&([^#])(?![A-Za-z0-9]{1,8};)/', '&amp;$1', $chunk);
            }
            $output .= $chunk;
        }

        return $output;
    }

    /**
     * Conver all URL to links in text
     *
     * Solution found on Stack Overflow, credits goes to its author.
     *   http://stackoverflow.com/questions/6393787/php-url-to-link-with-regex
     *
     * @param string $text
     *
     * @return string
     */
    public function urlToLinks($text)
    {
        return preg_replace('#(\A|[^=\]\'"a-zA-Z0-9])(http[s]?://(.+?)/[^()<>\s]+)#i', '\\1<a href="\\2">\\2</a>', $text);
    }

    public function filter($text)
    {
        // Input text is supposedly plain text
        // @todo Encoding here
        $text = htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED);

        // @todo This should be configurable per the user
        $text = $this->urlToLinks($text);
        $text = $this->autoParagraph($text);

        return $text;
    }
}
