<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

/**
 * DateTimeExtended class for holding the dateTime with attentional information.
 *
 * @internal
 */
class DateTimeExtended extends \DateTime
{
    private $hasTime = false;
    private $hasSeconds = false;

    /**
     * @param string  $time
     * @param boolean $hasTime
     */
    public function  __construct($time, $hasTime = false)
    {
        $this->hasTime = $hasTime;

        if ($hasTime && preg_match('#\d+:\d+:\d+$#', $time)) {
            $this->hasSeconds = true;
        }

        parent::__construct($time);
    }

    /**
     * @return boolean
     */
    public function hasTime()
    {
        return $this->hasTime;
    }

    /**
     * @return boolean
     */
    public function hasSeconds()
    {
        return $this->hasSeconds;
    }
}
