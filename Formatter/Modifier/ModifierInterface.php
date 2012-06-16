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
use Rollerworks\RecordFilterBundle\MessageBag;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

/**
 * ModifierInterface.
 *
 * Things to remember:
 *  * An modifier is performed multiple times.
 *  * An modifier is performed per FilterValuesBag per group.
 *  * An modifier can change the values in the FilterValuesBag.
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
     * @param FormatterInterface $formatter
     * @param MessageBag         $messageBag
     * @param FilterConfig       $filterConfig
     * @param FilterValuesBag    $valuesBag
     * @param integer            $groupIndex   Group the filter is in
     *
     * @return null|boolean
     *
     * @api
     */
    public function modFilters(FormatterInterface $formatter, MessageBag $messageBag, FilterConfig $filterConfig, FilterValuesBag $valuesBag, $groupIndex);
}
