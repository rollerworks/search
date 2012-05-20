<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Metadata\Driver;

use Metadata\Driver\DriverInterface;

/**
 * AbstractDriver
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Get the real type-class name
     *
     * @param string $type
     * @return null|string
     * @throws \InvalidArgumentException When the type can cant be found is or is not legal.
     */
    protected static function getRealType($type)
    {
        if (empty($type)) {
            return null;
        }

        if (false === strpos($type, '\\')) {
            $type = '\\Rollerworks\\RecordFilterBundle\\Type\\' . ucfirst($type);
        }
        else {
            $type = '\\'. ltrim($type, '\\');
        }

        if (!class_exists($type)) {
            throw new \InvalidArgumentException(sprintf('Failed to find the Filter-type "%s".', $type));
        }

        $r = new \ReflectionClass($type);

        if ($r->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Filter-type "%s" can\'t be abstract.', $type));
        }
        elseif (!$r->implementsInterface('\\Rollerworks\\RecordFilterBundle\\Type\\FilterTypeInterface')) {
            throw new \InvalidArgumentException(sprintf('Filter-type "%s" must implement Rollerworks\RecordFilterBundle\Type\FilterTypeInterface.', $type));
        }

        if ($r->hasMethod('__construct') && !$r->getMethod('__construct')->isPublic() ) {
            throw new \InvalidArgumentException(sprintf('%s::__construct(): must be public.', $type));
        }

        return $type;
    }
}
