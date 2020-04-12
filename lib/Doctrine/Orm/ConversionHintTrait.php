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

use Doctrine\ORM\Query\SqlWalker;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

/**
 * @internal
 */
trait ConversionHintTrait
{
    /**
     * @var QueryPlatform
     */
    protected $nativePlatform;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var QueryField[]
     */
    protected $fields = [];

    protected function loadConversionHints(SqlWalker $sqlWalker)
    {
        /* @var SqlConversionInfo $hintsValue */
        if (!($hintsValue = $sqlWalker->getQuery()->getHint('rws_conversion_hint'))) {
            throw new \LogicException('Missing "rws_conversion_hint" hint for '.static::class);
        }

        $this->nativePlatform = $hintsValue->nativePlatform;
        $this->parameters = $hintsValue->parameters;
        $this->fields = $hintsValue->fields;
    }
}
