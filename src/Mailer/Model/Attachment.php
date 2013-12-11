<?php

namespace Mailer\Model;

class Attachment extends AbstractObject
{
    private $name;

    private $mimetype;

    private $size;

    private $index;

    private $uid;

    public function toArray()
    {
        return parent::toArray($array) + array(
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

        parent::fromArray($array);

        $this->name     = $array['name'];
        $this->mimetype = $array['mimetype'];
        $this->size     = $array['size'];
        $this->index    = $array['index'];
        $this->uid      = $array['uid'];
    }
}
