<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Processor;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @link https://github.com/symfony/security/blob/master/Http/ParameterBagUtils.php
 */
final class RequestUtils
{
    private static $propertyAccessor;

    /**
     * Returns a "parameter" value.
     *
     * Paths like foo[bar] will be evaluated to find deeper items in nested data structures.
     *
     * @param ParameterBag $parameters The parameter bag
     * @param string       $path       The key
     *
     * @throws InvalidArgumentException when the given path is malformed
     *
     * @return mixed
     */
    public static function getParameterBagValue(ParameterBag $parameters, $path, $default = '')
    {
        if (false === $pos = strpos($path, '[')) {
            return $parameters->get($path, $default);
        }

        $root = substr($path, 0, $pos);

        if (null === $value = $parameters->get($root)) {
            return $default;
        }

        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            $value = self::$propertyAccessor->getValue($value, substr($path, $pos));

            return null === $value ? $default : $value;
        } catch (AccessException $e) {
            return $default;
        }
    }
}
