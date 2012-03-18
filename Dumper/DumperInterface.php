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

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * DumperInterface for dumping the filtering preference.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface DumperInterface
{
    /**
     * Dump the filters in a 'serialized' format.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface $formatter
     */
    public function dumpFilters(FormatterInterface $formatter);
}
