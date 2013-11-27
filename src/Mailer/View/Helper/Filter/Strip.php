<?php

namespace Mailer\View\Helper\Filter;

use Mailer\View\Helper\FilterInterface;

/**
 * Just strip HTML and attempt to convert a few valid tags in order to keep
 * minimal formatting = @todo
 */
class Strip implements FilterInterface
{
    public function filter($text)
    {
        // Before even removing any tags we should take care of what
        // is not visible (and potentially dangerous) and remove it
        $dropList = array('script', 'style', 'head', 'title', 'meta', 'link', 'noscript', 'embed');
        foreach ($dropList as $tag) {
            $text = preg_replace("#<" . $tag . ">.*</" . $tag . ">#ims", " ", $text);
            $text = preg_replace("#<" . $tag . "/>#ims", " ", $text);
        }

        // Remove all other tags (but just the tag, no the content)
        $text = preg_replace("/<.*?>/", " ", $text);

        // Remove all double space occurences
        $text = preg_replace("/ +/", " ", $text);
        $text = preg_replace("/(&nbsp;)+/", "&nbsp;", $text);
        $text = preg_replace("/( )+&nbsp;( )+/", " ", $text);

        return $text;
    }
}
