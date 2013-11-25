<?php

namespace Mailer\Mime\Part;

use Mailer\Mime\Part;

class Text extends Part
{
    /**
     * Body size in line count
     *
     * @var int
     */
    private $lineCount = 0;

    public function parseAdditionalData(array $array)
    {
        if (!empty($array)) {
            if ($part = array_shift($array)) {
                $this->setLineCount((int)$part);
            }
        }

        return $array;
    }

    /**
     * Set line count
     *
     * @param int $count
     */
    public function setLineCount($count)
    {
        $this->lineCount = $count;
    }

    /**
     * Get line count
     *
     * @return int
     */
    public function getLineCount()
    {
        return $this->lineCount;
    }
}
