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

/**
 * Formatter validation exception.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValidationException extends Exception
{
    protected $value;

    protected $validationMessage;

    protected $params = array();

    public function __construct($errorCode, $value = null, $transParams = array())
    {
        parent::__construct($errorCode);

        $this->params = $transParams;

        if (strlen($value)) {
            $this->params['%value%'] = $value;
        }
    }

    public function getParams()
    {
        return $this->params;
    }
}
