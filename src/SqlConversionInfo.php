<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatformInterface;

final class SqlConversionInfo implements \Serializable
{
    /**
     * @var QueryPlatformInterface
     */
    public $nativePlatform;

    /**
     * @var array
     */
    public $parameters;

    public function __construct(QueryPlatformInterface $nativePlatform, array $parameters)
    {
        $this->nativePlatform = $nativePlatform;
        $this->parameters = $parameters;
    }

    public function serialize()
    {
        return serialize($this->parameters);
    }

    /**
     * This does not nothing.
     */
    public function unserialize($serialized)
    {
        // noop
    }
}
