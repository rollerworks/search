<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter\Exception;

use Rollerworks\RecordFilterBundle\Exception;

class ReqFilterException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('required');
    }
}
