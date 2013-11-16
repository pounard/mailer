<?php

namespace Mailer\Model;

/**
 * Attempt to convert anything to a recursive array of primitive values
 */
class ArrayConverter
{
    private function recursiveSerialize($data)
    {
        $ret = array();

        if ($data instanceof ExchangeInterface) {
            $ret = $data->toArray();
        } else if ($data instanceof \Traversable || is_array($data)) {
            foreach ($data as $key => $item) {
                $ret[$key] = $this->recursiveSerialize($item);
            }
        } else {
            $ret = $data;
        }

        return $ret;
    }

    /**
     * Attempt to convert the data to an array
     *
     * @param mixed $data
     *
     * @return mixed|array
     */
    public function serialize($data)
    {
        return $this->recursiveSerialize($data);
    }
}
