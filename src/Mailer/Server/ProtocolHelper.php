<?php

namespace Mailer\Server;

class ProtocolHelper
{
    /**
     * Parse parameters list as an hashmap
     *
     * @param array $list
     *
     * @return array
     */
    static public function parseParameters(array $list)
    {
        $ret = array();

        $key = null;
        foreach ($list as $value) {
            if (null === $key) {
                $key = $value;
            } else {
                $ret[$key] = $value;
                $key = null;
            }
        }

        if (null !== $key) {
            // Malformed options list
            throw new \InvalidArgumentException("Malformed option list item count is odd");
        }

        return $ret;
    }
}
