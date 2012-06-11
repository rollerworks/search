<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Exception;

/**
 * ReqFilterException
 */
class ReqFilterException extends ValidationException
{
    public function __construct($label)
    {
        parent::__construct('required');

        $this->params['{{ label }}'] = $label;
    }
}
