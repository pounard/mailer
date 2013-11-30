<?php

namespace Mailer\Model;

class DateHelper
{
    /**
     * Get DateTime instance from RFC2822
     *
     * Examples of dates parsed:
     *   Mon, 15 Aug 2005 15:52:01 +0000
     *   Mon, 15 Aug 2005 15:52:01 +0000 (UTC)
     *   Mon, 15 Aug 2005 15:52:01 (UTC)
     *        19 Nov 2013 20:09:28 -0000
     *        19 Nov 2013 20:09:28 -0000 (UTC)
     *        19 Nov 2013 20:09:28 (UTC)
     *
     * @param string $dateString
     *
     * @return \DateTime
     *   Instance or null
     */
    static public function fromRfc2822($dateString)
    {
        // Some give the timezone another way
        if (strpos($dateString, " (")) {
            list($dateString, $timezone) = explode(" (", $dateString);
            if ($pos = strpos($timezone, ")")) {
                $timezone = substr($timezone, 0, $pos);
            }
            $timezone = new \DateTimeZone($timezone);
        } else {
            $timezone = null;
        }

        // Some don't give the day
        if (false === strpos($dateString, ',')) {
            $format = "d M Y H:i:s O";
        } else {
            $format = \DateTime::RFC2822;
        }

        if (null === $timezone) {
            $date = \DateTime::createFromFormat($format, $dateString);
        } else {
            $date = \DateTime::createFromFormat($format, $dateString, $timezone);
        }

        if ($date) { // Needs this because createFromFormat() can return false
            return $date;
        }
    }
}
