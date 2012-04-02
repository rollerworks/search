<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Formatter;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * DomainAwareFormatter interface should be implemented by formatter classes that are domain-aware.
 *
 * An formatter is domain-aware when the configuration only applies to one class (domain).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface DomainAwareFormatterInterface extends FormatterInterface
{
    /**
     * Returns the class name from which the configuration was read.
     *
     * @return string
     */
    public function getBaseClassName();
}