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
 * Validation exception.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValidationException extends Exception
{
    /**
     * @var array
     */
    protected $params = array();

    /**
     * @param string $errorCode
     * @param array  $transParams
     */
    public function __construct($errorCode, array $transParams = array())
    {
        parent::__construct($errorCode);

        $this->params = $transParams;
    }

    /**
     * Get the parameters of the message.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
