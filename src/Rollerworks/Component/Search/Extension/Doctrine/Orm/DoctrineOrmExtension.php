<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Rollerworks\Component\Search\AbstractExtension;

/**
 * Represents the doctrine ORM extension,
 * for the core Doctrine ORM functionality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineOrmExtension extends AbstractExtension
{
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        return array(
            new Type\EntityCountType(),
        );
    }
}
