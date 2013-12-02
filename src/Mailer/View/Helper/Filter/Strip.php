<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

/**
 * Just strip HTML and attempt to convert a few valid tags in order to keep
 * minimal formatting = @todo
 */
class Strip implements FilterInterface
{
    public function filter($text, $charset = null)
    {
        // Before even removing any tags we should take care of what
        // is not visible (and potentially dangerous) and remove it
        $dropList = array('script', 'style', 'head', 'title', 'meta', 'link', 'noscript', 'embed');

        // HTML document will advertise the wrong charset since we actually
        // converted it before it ends up in here, change it
        $d = new \DOMDocument('1.0', $charset);
        if (@$d->loadHTML($text)) {
            $x = new \DOMXPath($d);
            foreach ($dropList as $tag) {
                $nodes = $x->query('//' . $tag);
                foreach ($nodes as $node) {
                    $node->parentNode->removeChild($node);
                }
            }
            $text = $d->saveHTML();
        } else {
            // If HTML is invalid fallback to a stupid, stupid mode
            foreach ($dropList as $tag) {
                $text = preg_replace("#<" . $tag . "(\s.*|)>.*</" . $tag . ">#ims", " ", $text);
                $text = preg_replace("#<" . $tag . "(\s.*|)/>#ims", " ", $text);
            }
        }

        // Remove all other tags (but just the tag, no the content)
        $text = preg_replace("/<.*?>/", " ", $text);

        // Remove all double space occurences
        $text = str_replace("\r", "", $text);
        $text = str_replace("\t", "", $text);
        $text = preg_replace("/[\n ]{3,}/", " ", $text);
        $text = preg_replace("/(&nbsp;)+/", "&nbsp;", $text);
        $text = preg_replace("/( )+&nbsp;( )+/", " ", $text);

        return $text;
    }
}
