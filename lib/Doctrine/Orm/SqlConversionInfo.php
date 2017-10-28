<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

final class SqlConversionInfo implements \Serializable
{
    /**
     * @var QueryPlatform
     */
    public $nativePlatform;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @var array
     */
    public $fields;

    public function __construct(QueryPlatform $nativePlatform, array $parameters, array $fields)
    {
        $this->nativePlatform = $nativePlatform;
        $this->parameters = $parameters;
        $this->fields = $fields;
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
        // no-op
    }
}
