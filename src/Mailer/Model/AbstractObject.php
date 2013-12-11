<?php

namespace Mailer\Model;

class AbstractObject implements ExchangeInterface
{
    /**
     * @var string
     */
    private $checksum;

    public function getChecksum()
    {
        return $this->checksum;
    }

    public function regenerateChecksum()
    {
        $this->checksum = md5(uniqid(rand(), true));
    }

    public function toArray()
    {
        return array(
            'checksum' => $this->checksum,
        );
    }

    public function fromArray(array $array)
    {
        if (empty($array['checksum'])) {
            $this->regenerateChecksum();
        } else {
            $this->checksum = $array['checksum'];
        }
    }
}
