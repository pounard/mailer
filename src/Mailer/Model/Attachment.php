<?php

namespace Mailer\Model;

class Attachment implements ExchangeInterface
{
    private $name;

    private $mimetype;

    private $size;

    private $index;

    private $uid;

    public function toArray()
    {
        return array(
            'name'     => $this->name,
            'mimetype' => $this->mimetype,
            'size'     => $this->size,
            'index'    => $this->index,
            'uid'      => $this->uid,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        $this->name     = $array['name'];
        $this->mimetype = $array['mimetype'];
        $this->size     = $array['size'];
        $this->index    = $array['index'];
        $this->uid      = $array['uid'];
    }
}
