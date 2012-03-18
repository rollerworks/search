<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
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
