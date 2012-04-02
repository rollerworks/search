<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Pre modifier interface.
 *
 * Must be in implemented by the Formatter Pre-modifier.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface PreModifierInterface
{
    /**
     * Returns the name of the modifier.
     * This would normally be the class-name in lowercase and underscored.
     *
     * @return string
     */
    public function getModifierName();

    /**
     * Modify the filters and returns them.
     * Like: name => [value1, value2]
     *
     * Returns the modified filter list.
     *
     * @param FormatterInterface    $formatter
     * @param array                 $filters
     * @param integer               $groupIndex
     * @return array
     */
    public function modFilters(FormatterInterface $formatter, $filters, $groupIndex);
}