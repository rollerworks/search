<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata;

use Rollerworks\Component\Search\Util\XmlUtils;

/**
 * SimpleXMLElement class.
 *
 * Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SimpleXMLElement extends \SimpleXMLElement
{
    /**
     * Converts an attribute as a php type.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttributeAsPhp($name)
    {
        return self::phpize($this[$name]);
    }

    /**
     * Returns arguments as valid php types.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getArgumentsAsPhp($name)
    {
        $arguments = array();
        foreach ($this->$name as $arg) {
            if (isset($arg['name'])) {
                $arg['key'] = (string) $arg['name'];
            }

            $key = isset($arg['key']) ? (string) $arg['key'] : (!$arguments ? 0 : max(array_keys($arguments)) + 1);

            switch ($arg['type']) {
                case 'collection':
                    $arguments[$key] = $arg->getArgumentsAsPhp($name);
                    break;

                case 'string':
                    $arguments[$key] = (string) $arg;
                    break;

                default:
                    $arguments[$key] = self::phpize($arg);
            }
        }

        return $arguments;
    }

    /**
     * Converts an xml value to a php type.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function phpize($value)
    {
        return XmlUtils::phpize($value);
    }
}
