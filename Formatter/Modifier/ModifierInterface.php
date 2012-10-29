<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;

/**
 * ModifierInterface.
 *
 * Things to remember:
 *
 *  * A modifier is performed multiple times.
 *  * A modifier is performed per FilterValuesBag object per group.
 *  * A modifier can change the values in the FilterValuesBag.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface ModifierInterface
{
    /**
     * Returns the name of the modifier.
     *
     * This would normally be the class-name in lowercase and underscored.
     *
     * @return string
     *
     * @api
     */
    public function getModifierName();

    /**
     * Modifies the filters.
     *
     * Return null to remove the filter from the final result.
     * Return false to skip other modifiers.
     *
     * @param FormatterInterface $formatter    Formatter object instance performing the modifiers
     * @param MessageBag         $messageBag   MessageBag object instance for adding messages
     * @param FilterField        $filterConfig Current FilterField to process
     * @param FilterValuesBag    $valuesBag    FilterValuesBag object of the filter field
     * @param integer            $groupIndex   Group the filter is in (starting from 0)
     *
     * @return null|boolean
     *
     * @api
     */
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterField $filterConfig, FilterValuesBag $valuesBag, $groupIndex);
}
