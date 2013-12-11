<?php

namespace Mailer\Model;

abstract class AbstractContainer extends AbstractObject
{
    protected $created;

    protected $updated;

    protected $total = 1;

    protected $unseen = 0;

    protected $recent = 0;

    public function getCreationDate()
    {
        return $this->created;
    }

    public function getLastUpdate()
    {
        return $this->updated;
    }

    public function getMessageCount()
    {
        return $this->messageCount;
    }

    public function getUnseenCount()
    {
        return $this->unseenCount;
    }

    public function getRecentCount()
    {
        return $this->recentCount;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'created' => $this->created,
            'updated' => $this->updated,
            'total'   => $this->total,
            'unseen'  => $this->unseen,
            'recent'  => $this->recent,
        );
    }

    public function fromArray(array $array)
    {
        $array += $this->toArray();

        parent::fromArray($array);

        $this->created = $array['created'];
        $this->updated = $array['updated'];
        $this->total   = (int)$array['total'];
        $this->unseen  = (int)$array['unseen'];
        $this->recent  = (int)$array['recent'];
    }
}
