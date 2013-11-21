<?php

namespace Mailer\Model;

class DateHelper
{
    /**
     * Get DateTime instance from RFC2822
     *
     * @param string $dateString
     *
     * @return \DateTime
     *   Instance or null
     */
    static public function fromRfc2822($dateString)
    {
        if (strpos($dateString, " (")) {
            list($dateString, $timezone) = explode(" (", $dateString);
            if ($pos = strpos($timezone, ")")) {
                $timezone = substr($timezone, 0, $pos);
            }
            $timezone = new \DateTimeZone($timezone);
        } else {
            $timezone = null;
        }

        if (null === $timezone) {
            $date = \DateTime::createFromFormat(\DateTime::RFC2822, $dateString);
        } else {
            $date = \DateTime::createFromFormat(\DateTime::RFC2822, $dateString, $timezone);
        }

        if ($date) {
            return $date;
        }
    }
}
