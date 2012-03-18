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

namespace Rollerworks\RecordFilterBundle\Input;


/**
 * Interface for supplying input-values
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputInterface
{
    /**
     * Get the input-values by field.
     * The values are not formatted or validated.
     *
     * Returns the fields per group, like:
     * [group-n] => array('field-name' => 'values')
     *
     * Depending on hasGroups(), the number of groups varies.
     * When there are no values, an empty array is returned.
     *
     * @return array
     */
    public function getValues();

    /**
     * Returns whether the value list is an or-case.
     *
     * @return boolean
     */
    public function hasGroups();
}