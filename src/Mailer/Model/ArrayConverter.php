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
            $data = $data->toArray();
        }

        if ($data instanceof \Exception) {
            $ret = array(
                'type' => 'error',
                'message' => $data->getMessage(),
            );
            foreach ($data->getTrace() as $trace) {
                unset($trace['args']);
                $ret['trace'][] = $trace;
            }
        }
        if ($data instanceof \Traversable || is_array($data)) {
            foreach ($data as $key => $item) {
                $ret[$key] = $this->recursiveSerialize($item);
            }
        } else if ($data instanceof \DateTime) {
            $ret = $data->format(\DateTime::ISO8601);
        } else if (is_scalar($data)) {
            $ret = $data;
        } else {
            // trigger_error("Property could not be converted");
            $ret = null;
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
